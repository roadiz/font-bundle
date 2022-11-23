<?php

declare(strict_types=1);

namespace RZ\Roadiz\FontBundle\Doctrine\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\FontBundle\Entity\Font;

/**
 * Handle file management on Fonts lifecycle events.
 */
final class FontLifeCycleSubscriber implements EventSubscriber
{
    private LoggerInterface $logger;
    private FilesystemOperator $fontStorage;

    public function __construct(FilesystemOperator $fontStorage, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->fontStorage = $fontStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
            Events::preRemove,
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        // perhaps you only want to act on some "Font" entity
        if ($entity instanceof Font) {
            $this->setFontFilesNames($entity);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        // perhaps you only want to act on some "Font" entity
        if ($entity instanceof Font) {
            $this->setFontFilesNames($entity);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @throws FilesystemException
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        // perhaps you only want to act on some "Font" entity
        if ($entity instanceof Font) {
            $this->upload($entity);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @throws FilesystemException
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        // perhaps you only want to act on some "Font" entity
        if ($entity instanceof Font) {
            $this->upload($entity);
        }
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        // perhaps you only want to act on some "Product" entity
        if ($entity instanceof Font) {
            try {
                if (null !== $entity->getSVGFilename() && $this->fontStorage->fileExists($entity->getSVGRelativeUrl())) {
                    $this->fontStorage->delete($entity->getSVGRelativeUrl());
                    $this->logger->info('Font file deleted', ['file' => $entity->getSVGRelativeUrl()]);
                }
                if (null !== $entity->getOTFFilename() && $this->fontStorage->fileExists($entity->getOTFRelativeUrl())) {
                    $this->fontStorage->delete($entity->getOTFRelativeUrl());
                    $this->logger->info('Font file deleted', ['file' => $entity->getOTFRelativeUrl()]);
                }
                if (null !== $entity->getEOTFilename() && $this->fontStorage->fileExists($entity->getEOTRelativeUrl())) {
                    $this->fontStorage->delete($entity->getEOTRelativeUrl());
                    $this->logger->info('Font file deleted', ['file' => $entity->getEOTRelativeUrl()]);
                }
                if (null !== $entity->getWOFFFilename() && $this->fontStorage->fileExists($entity->getWOFFRelativeUrl())) {
                    $this->fontStorage->delete($entity->getWOFFRelativeUrl());
                    $this->logger->info('Font file deleted', ['file' => $entity->getWOFFRelativeUrl()]);
                }
                if (null !== $entity->getWOFF2Filename() && $this->fontStorage->fileExists($entity->getWOFF2RelativeUrl())) {
                    $this->fontStorage->delete($entity->getWOFF2RelativeUrl());
                    $this->logger->info('Font file deleted', ['file' => $entity->getWOFF2RelativeUrl()]);
                }

                /*
                 * Removing font folder if empty.
                 */
                $fontFolder = $entity->getFolder();
                if ($this->fontStorage->directoryExists($fontFolder)) {
                    $dirListing = $this->fontStorage->listContents($fontFolder);
                    $isDirEmpty = \count($dirListing->toArray()) <= 0;
                    if ($isDirEmpty) {
                        $this->logger->info('Font folder is empty, deleting…', ['folder' => $fontFolder]);
                        $this->fontStorage->deleteDirectory($fontFolder);
                    }
                }
            } catch (FilesystemException $e) {
                //do nothing
            }
        }
    }

    public function setFontFilesNames(Font $font)
    {
        if ($font->getHash() == "") {
            $font->generateHashWithSecret('default_roadiz_secret');
        }

        if (null !== $font->getSvgFile()) {
            $font->setSVGFilename($font->getSvgFile()->getClientOriginalName());
        }
        if (null !== $font->getOtfFile()) {
            $font->setOTFFilename($font->getOtfFile()->getClientOriginalName());
        }
        if (null !== $font->getEotFile()) {
            $font->setEOTFilename($font->getEotFile()->getClientOriginalName());
        }
        if (null !== $font->getWoffFile()) {
            $font->setWOFFFilename($font->getWoffFile()->getClientOriginalName());
        }
        if (null !== $font->getWoff2File()) {
            $font->setWOFF2Filename($font->getWoff2File()->getClientOriginalName());
        }
    }

    /**
     * @param Font $font
     * @return void
     * @throws FilesystemException
     */
    public function upload(Font $font): void
    {
        if (null !== $font->getSvgFile()) {
            $this->fontStorage->write($font->getSVGRelativeUrl(), \file_get_contents($font->getSvgFile()->getPathname()));
            $font->setSvgFile(null);
        }
        if (null !== $font->getOtfFile()) {
            $this->fontStorage->write($font->getOTFRelativeUrl(), \file_get_contents($font->getOtfFile()->getPathname()));
            $font->setOtfFile(null);
        }
        if (null !== $font->getEotFile()) {
            $this->fontStorage->write($font->getEOTRelativeUrl(), \file_get_contents($font->getEotFile()->getPathname()));
            $font->setEotFile(null);
        }
        if (null !== $font->getWoffFile()) {
            $this->fontStorage->write($font->getWOFFRelativeUrl(), \file_get_contents($font->getWoffFile()->getPathname()));
            $font->setWoffFile(null);
        }
        if (null !== $font->getWoff2File()) {
            $this->fontStorage->write($font->getWOFF2RelativeUrl(), \file_get_contents($font->getWoff2File()->getPathname()));
            $font->setWoff2File(null);
        }
    }
}
