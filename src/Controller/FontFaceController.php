<?php

declare(strict_types=1);

namespace RZ\Roadiz\FontBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Documents\Models\FileAwareInterface;
use RZ\Roadiz\Documents\Packages;
use RZ\Roadiz\FontBundle\Entity\Font;
use RZ\Roadiz\FontBundle\Repository\FontRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class FontFaceController
{
    private FileAwareInterface $fileAware;
    private ManagerRegistry $managerRegistry;
    private Environment $templating;
    private Packages $packages;

    public function __construct(
        FileAwareInterface $fileAware,
        ManagerRegistry $managerRegistry,
        Environment $templating,
        Packages $packages
    ) {
        $this->fileAware = $fileAware;
        $this->managerRegistry = $managerRegistry;
        $this->templating = $templating;
        $this->packages = $packages;
    }

    /**
     * Request a single protected font file from Roadiz.
     *
     * @param Request $request
     * @param string  $filename
     * @param int     $variant
     * @param string  $extension
     *
     * @return Response
     * @throws \Exception
     */
    public function fontFileAction(Request $request, string $filename, int $variant, string $extension): Response
    {
        /** @var FontRepository $repository */
        $repository = $this->managerRegistry->getRepository(Font::class);
        $lastMod = $repository->getLatestUpdateDate();
        /** @var \RZ\Roadiz\FontBundle\Entity\Font $font */
        $font = $repository->findOneBy(['hash' => $filename, 'variant' => $variant]);

        if (null !== $font) {
            switch ($extension) {
                case 'eot':
                    $fontpath = $this->packages->getFontsPath($font->getEOTRelativeUrl());
                    $mime = Font::MIME_EOT;
                    break;
                case 'woff':
                    $fontpath = $this->packages->getFontsPath($font->getWOFFRelativeUrl());
                    $mime = Font::MIME_WOFF;
                    break;
                case 'woff2':
                    $fontpath = $this->packages->getFontsPath($font->getWOFF2RelativeUrl());
                    $mime = Font::MIME_WOFF2;
                    break;
                case 'svg':
                    $fontpath = $this->packages->getFontsPath($font->getSVGRelativeUrl());
                    $mime = Font::MIME_SVG;
                    break;
                case 'otf':
                    $mime = Font::MIME_OTF;
                    $fontpath = $this->packages->getFontsPath($font->getOTFRelativeUrl());
                    break;
                case 'ttf':
                    $mime = Font::MIME_TTF;
                    $fontpath = $this->packages->getFontsPath($font->getOTFRelativeUrl());
                    break;
                default:
                    $fontpath = null;
                    $mime = "application/octet-stream";
                    break;
            }

            if (null !== $fontpath && file_exists($fontpath) && is_file($fontpath)) {
                $response = new Response(
                    '',
                    Response::HTTP_NOT_MODIFIED,
                    [
                        'content-type' => $mime,
                    ]
                );
                $response->setCache([
                    'last_modified' => new \DateTime($lastMod),
                    'max_age' => 60 * 60 * 48, // expires for 2 days
                    'public' => true,
                ]);
                if (!$response->isNotModified($request)) {
                    $response->setContent(file_get_contents($fontpath));
                    $response->setStatusCode(Response::HTTP_OK);
                    $response->setEtag(md5($response->getContent()));
                }

                return $response;
            }
        }
        $msg = "Font doesn't exist " . $filename;

        return new Response(
            $msg,
            Response::HTTP_NOT_FOUND,
            ['content-type' => 'text/html']
        );
    }

    /**
     * Request the font-face CSS file listing available fonts.
     *
     * @param Request $request
     *
     * @return Response
     * @throws \Exception
     */
    public function fontFacesAction(Request $request): Response
    {
        /** @var \RZ\Roadiz\FontBundle\Repository\FontRepository $repository */
        $repository = $this->managerRegistry->getRepository(Font::class);
        $lastMod = $repository->getLatestUpdateDate();

        $response = new Response(
            '',
            Response::HTTP_NOT_MODIFIED,
            ['content-type' => 'text/css']
        );
        $cacheConfig = [
            'max_age' => 60 * 60 * 48, // expires for 2 days
            'public' => true,
        ];
        if (null !== $lastMod) {
            $cacheConfig['last_modified'] = new \DateTime($lastMod);
        }
        $response->setCache($cacheConfig);

        if ($response->isNotModified($request)) {
            return $response;
        }

        $fonts = $repository->findAll();

        $assignation = [
            'fonts' => [],
        ];
        /** @var \RZ\Roadiz\FontBundle\Entity\Font $font */
        foreach ($fonts as $font) {
            $variantHash = $font->getHash() . $font->getVariant();
            $assignation['fonts'][] = [
                'font' => $font,
                'fontFolder' => $this->fileAware->getFontsFilesBasePath(),
                'variantHash' => $variantHash,
            ];
        }
        $response->setContent(
            $this->templating->render(
                '@RoadizFont/fonts/fontfamily.css.twig',
                $assignation
            )
        );
        $response->setEtag(md5($response->getContent()));
        $response->setStatusCode(Response::HTTP_OK);

        return $response;
    }
}
