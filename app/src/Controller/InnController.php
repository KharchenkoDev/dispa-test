<?php

namespace App\Controller;

use App\Entity\InnLookup;
use App\Repository\InnLookupRepository;
use App\Service\DadataService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class InnController extends AbstractController
{
    #[Route('/inn/{inn}', name: 'api_inn_check', methods: ['GET'])]
    public function check(
        string $inn,
        InnLookupRepository $repository,
        DadataService $dadataService,
        EntityManagerInterface $em,
    ): JsonResponse {
        if (!preg_match('/^\d{10}(\d{2})?$/', $inn)) {
            return $this->json(['error' => 'Некорректный формат ИНН. Ожидается 10 или 12 цифр.'], 400);
        }

        $lookup = $repository->findOneBy(['inn' => $inn]);

        if ($lookup === null) {
            try {
                $suggestion = $dadataService->findByInn($inn);
            } catch (\RuntimeException $e) {
                return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 502);
            }

            if (empty($suggestion)) {
                return $this->json(['error' => 'ИНН не найден.'], 404);
            }

            $data = $suggestion['data'];

            $lookup = (new InnLookup())
                ->setInn($inn)
                ->setName($data['name']['short_with_opf'] ?? $data['name']['full_with_opf'] ?? $suggestion['value'])
                ->setIsActive($data['state']['status'] === 'ACTIVE')
                ->setOkved($data['okved'] ?? '')
                ->setOkvedName($data['okved_name'] ?? '')
                ->setRawResponse($data)
                ->setCreatedAt(new \DateTimeImmutable());

            $em->persist($lookup);
            $em->flush();
        }

        return $this->json([
            'inn'        => $lookup->getInn(),
            'name'       => $lookup->getName(),
            'is_active'  => $lookup->isActive(),
            'okved'      => $lookup->getOkved(),
            'okved_name' => $lookup->getOkvedName(),
        ]);
    }
}
