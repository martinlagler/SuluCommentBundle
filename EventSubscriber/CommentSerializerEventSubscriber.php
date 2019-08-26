<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CommentBundle\EventSubscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Sulu\Bundle\CommentBundle\Entity\Comment;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CommentSerializerEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(MediaManagerInterface $mediaManager, RequestStack $requestStack)
    {
        $this->mediaManager = $mediaManager;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
            ],
        ];
    }

    /**
     * @param ObjectEvent $event
     *
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        /** @var Comment $comment */
        $comment = $event->getObject();
        if (!$comment instanceof Comment || !$creator = $comment->getCreator() || !$currentRequest = $this->requestStack->getCurrentRequest()) {
            return;
        }

        $contact = $creator->getContact();
        if (!$avatar = $contact->getAvatar()) {
            return;
        }

        $avatar = $this->mediaManager->getById($avatar->getId(), $currentRequest->getLocale());

        $event->getVisitor()->addData('creatorAvatar', $event->getContext()->accept([
            'id' => $avatar->getId(),
            'title' => $avatar->getTitle(),
            'description' => $avatar->getDescription(),
            'credits' => $avatar->getCredits(),
            'name' => $avatar->getName(),
            'formats' => $avatar->getFormats(),
            'url' => $avatar->getUrl(),
        ]));
    }
}
