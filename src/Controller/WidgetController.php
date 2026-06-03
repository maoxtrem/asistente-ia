<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final class WidgetController
{
    public function __construct(private readonly Environment $twig)
    {
    }

    #[Route('/asistente-ia/widget', name: 'asistente_ia_widget', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response($this->twig->render('@AsistenteIa/widget/bubble.html.twig'));
    }
}
