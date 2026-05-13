<?php

namespace App\Controller;

use App\Entity\InnLookup;
use App\Repository\InnLookupRepository;
use App\Service\DadataService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
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
        ManagerRegistry $registry,
    ): JsonResponse {
        if (!preg_match('/^\d{10}(\d{2})?$/', $inn)) {
            return $this->json(['error' => 'Некорректный формат ИНН. Ожидается 10 или 12 цифр.'], 400);
        }

        $lookup = $repository->findOneBy(['inn' => $inn]);

        if (null !== $lookup && !$lookup->isStale()) {
            return $this->json($this->serialize($lookup));
        }

        try {
            $suggestion = $dadataService->findByInn($inn);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 502);
        }

        if (empty($suggestion)) {
            return $this->json(['error' => 'ИНН не найден.'], 404);
        }

        if (null === $lookup) {
            $lookup = new InnLookup()->setInn($inn)->setCreatedAt(new \DateTimeImmutable());
        }

        $this->hydrate($lookup, $suggestion);

        try {
            $em->persist($lookup);
            $em->flush();
        } catch (UniqueConstraintViolationException) {
            // Параллельный запрос успел сохранить первым — сбрасываем EM и читаем свежую запись
            $freshEm = $registry->resetManager();
            $lookup = $freshEm->getRepository(InnLookup::class)->findOneBy(['inn' => $inn]);

            if (null === $lookup) {
                return $this->json(['error' => 'Не удалось получить данные после конкурентного сохранения.'], 500);
            }
        }

        return $this->json($this->serialize($lookup));
    }

    private function hydrate(InnLookup $lookup, array $suggestion): void
    {
        $data = $suggestion['data'] ?? [];
        $status = $data['state']['status'] ?? null;

        $lookup
            ->setName($data['name']['short_with_opf'] ?? $data['name']['full_with_opf'] ?? $suggestion['value'] ?? '')
            ->setIsActive('ACTIVE' === $status)
            ->setOkved($data['okved'] ?? '')
            ->setOkvedName($data['okved_name'] ?? '')
            ->setRawResponse($data)
            ->setUpdatedAt(new \DateTimeImmutable());
    }

    private function serialize(InnLookup $lookup): array
    {
        return [
            'inn' => $lookup->getInn(),
            'name' => $lookup->getName(),
            'is_active' => $lookup->isActive(),
            'okved' => $lookup->getOkved(),
            'okved_name' => $lookup->getOkvedName(),
        ];
    }
}
