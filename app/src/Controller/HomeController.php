<?php

namespace App\Controller;

use App\Repository\InnLookupRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    private const PER_PAGE = 50;

    #[Route('/', name: 'home')]
    public function index(Request $request, InnLookupRepository $repository): Response
    {
        $page  = max(1, $request->query->getInt('page', 1));
        $total = $repository->countAll();
        $pages = (int) ceil($total / self::PER_PAGE);
        $page  = min($page, max(1, $pages));

        return $this->render('home/index.html.twig', [
            'items'   => $repository->findPaginated($page, self::PER_PAGE),
            'page'    => $page,
            'pages'   => $pages,
            'total'   => $total,
        ]);
    }
}
