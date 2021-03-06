<?php

namespace Cocorico\MessageBundle\Controller\Dashboard;

use Cocorico\CoreBundle\Entity\Listing;
use Cocorico\MessageBundle\Entity\Message;
use Cocorico\MessageBundle\Entity\Thread;
use Cocorico\MessageBundle\Event\MessageEvent;
use Cocorico\MessageBundle\Event\MessageEvents;
use Cocorico\MessageBundle\Repository\MessageRepository;
use FOS\MessageBundle\Model\ParticipantInterface;
use FOS\MessageBundle\Model\ThreadInterface;
use FOS\MessageBundle\Provider\ProviderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Message controller.
 *
 * @Route("/message")
 */
class MessageController extends Controller
{

    /**
     * lists the available messages
     *
     * @Method("GET")
     * @Route("/{page}", name="cocorico_dashboard_message", requirements={"page" = "\d+"}, defaults={"page" = 1})
     *
     * @param Request $request
     * @param Integer $page
     * @return Response
     */
    public function indexAction(Request $request, $page)
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof ParticipantInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $userType = $request->getSession()->get('profile', 'asker');
        $threadManager = $this->get('cocorico_message.thread_manager');
        $threads = $threadManager->getListingInboxThreads($user, $userType, $page);

        return $this->render(
            'CocoricoMessageBundle:Dashboard/Message:inbox.html.twig',
            array(
                'threads' => $threads,
                'pagination' => array(
                    'page' => $page,
                    'pages_count' => ceil($threads->count() / $threadManager->maxPerPage),
                    'route' => $request->get('_route'),
                    'route_params' => $request->query->all()
                ),
            )
        );

    }

    /**
     * Creates a new message thread.
     *
     * @Route("/{slug}/new", name="cocorico_dashboard_message_new", requirements={
     *      "slug" = "[a-z0-9-]+$"
     * })
     *
     * @Method({"GET", "POST"})
     *
     * @Security("is_granted('view', listing)")
     * @ParamConverter("listing", class="Cocorico\CoreBundle\Entity\Listing", options={"repository_method" = "findOneBySlug"})
     *
     * @param Request $request
     * @param Listing $listing
     * @return RedirectResponse
     */
    public function newThreadAction(Request $request, Listing $listing)
    {
        /** @var Form $form */
        $form = $this->get('fos_message.new_thread_form.factory')->create();
        $formHandler = $this->get('fos_message.new_thread_form.handler');

        /** @var Thread $thread */
        $thread = $form->getData();
        $thread->setListing($listing);
        $thread->setSubject($listing->getTitle());
        $thread->setRecipient($listing->getUser());
        $form->setData($thread);

        /** @var Message $message */
        $message = $formHandler->process($form);

        $translator = $this->get('translator');
        $session = $this->get('session');

        if ($message) {
            $messageEvent = new MessageEvent($message->getThread(), $listing->getUser(), $this->getUser());
            $this->get('event_dispatcher')->dispatch(MessageEvents::MESSAGE_POST_SEND, $messageEvent);

            $this->get('doctrine')->getManager()->getRepository('CocoricoMessageBundle:Message')
                ->clearNbUnreadMessageCache($listing->getUser()->getId());

            $url = $this->generateUrl(
                'cocorico_dashboard_message_new',
                array(
                    'slug' => $listing->getSlug(),
                )
            );

            $session->getFlashBag()->add(
                'success',
                $translator->trans('message.new.success', array(), 'cocorico_message')
            );

            return $this->redirect($url);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $session->getFlashBag()->add(
                'error',
                $translator->trans('message.new.error', array(), 'cocorico_message')
            );
        }

        return $this->get('templating')->renderResponse(
            'CocoricoMessageBundle:Dashboard/Message:new_thread.html.twig',
            array(
                'form' => $form->createView(),
                'thread' => $form->getData(),
                'listing' => $listing
            )
        );
    }

    /**
     * Displays a thread, also allows to reply to it.
     * @Route("/conversation/{threadId}", name="cocorico_dashboard_message_thread_view", requirements={"threadId" = "\d+"})
     *
     * Security is managed by FOSMessageProvider
     *
     * @param Request $request
     * @param         $threadId
     * @return RedirectResponse
     */
    public function threadAction(Request $request, $threadId)
    {
        /* @var Thread $thread */
        $thread = $this->getProvider()->getThread($threadId);

        $this->get('doctrine')->getManager()->getRepository('CocoricoMessageBundle:Message')
            ->clearNbUnreadMessageCache($this->getUser()->getId());

        /** @var Form $form */
        $form = $this->get('fos_message.reply_form.factory')->create($thread);
        $paramArr = $request->get($form->getName());
        $request->request->set($form->getName(), $paramArr);

        $formHandler = $this->get('fos_message.reply_form.handler');

        $selfUrl = $this->get('router')->generate(
            'cocorico_dashboard_message_thread_view',
            array('threadId' => $thread->getId())
        );

        if ($formHandler->process($form)) {
            $recipients = $thread->getOtherParticipants($this->getUser());
            $recipient = (count($recipients) > 0) ? $recipients[0] : $this->getUser();

            $messageEvent = new MessageEvent($thread, $recipient, $this->getUser());
            $this->get('event_dispatcher')->dispatch(MessageEvents::MESSAGE_POST_SEND, $messageEvent);

            return new RedirectResponse($selfUrl);
        }

        //Breadcrumbs
        $breadcrumbs = $this->get('cocorico.breadcrumbs_manager');
        $breadcrumbs->addThreadViewItems($request, $thread, $this->getUser());

        return $this->get('templating')->renderResponse(
            'CocoricoMessageBundle:Dashboard/Message:thread.html.twig',
            array(
                'form' => $form->createView(),
                'thread' => $thread
            )
        );
    }

    /**
     * Deletes a thread
     *
     * @Route("/delete/{threadId}", name="cocorico_dashboard_message_thread_delete", requirements={"threadId" = "\d+"})
     *
     * Security is managed by FOSMessageProvider
     *
     * @param string $threadId the thread id
     *
     * @return RedirectResponse
     */
    public function deleteAction($threadId)
    {
        /** @var ThreadInterface $thread */
        $thread = $this->getProvider()->getThread($threadId);
        $this->get('fos_message.deleter')->markAsDeleted($thread);
        $this->get('fos_message.thread_manager')->saveThread($thread);

        $this->get('doctrine')->getManager()->getRepository('CocoricoMessageBundle:Message')
            ->clearNbUnreadMessageCache($this->getUser()->getId());

        return new RedirectResponse(
            $this->get('router')->generate('cocorico_dashboard_message')
        );
    }

    /**
     * Gets the provider service
     *
     * @return ProviderInterface
     */
    protected function getProvider()
    {
        return $this->get('fos_message.provider');
    }

    /**
     * Get number of unread messages for user
     *
     * @Route("/get-nb-unread-messages", name="cocorico_dashboard_message_nb_unread")
     *
     * @Method("GET")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function nbUnReadMessagesAction(Request $request)
    {
        $response = array('asker' => 0, 'offerer' => 0, 'total' => 0);
        if ($request->isXmlHttpRequest()) {
            $user = $this->getUser();
            $em = $this->get('doctrine')->getManager();
            /** @var MessageRepository $repo */
            $repo = $em->getRepository('CocoricoMessageBundle:Message');
            $nbMessages = $repo->getNbUnreadMessage($user, true);

            $response['asker'] = ($nbMessages[0]['asker']) ? $nbMessages[0]['asker'] : 0;
            $response['offerer'] = $nbMessages[0]['offerer'] ? $nbMessages[0]['offerer'] : 0;
            $response['total'] = $response['asker'] + $response['offerer'];
        }

        return new JsonResponse($response);
    }

}
