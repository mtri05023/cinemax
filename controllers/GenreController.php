<?php
require_once __DIR__ . '/../services/GenreService.php';

class GenreController
{
    private GenreService $genreService;

    public function __construct(GenreService $genreService)
    {
        $this->genreService = $genreService;
    }

    public function create(): void
    {
        try {
            $name = $_POST['name'] ?? '';
            $this->genreService->create($name);

            header('Location: ../../views/admin/index.php?page=genres&add=1');
            exit;
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=genres&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function delete($id): void
    {
        try {
            $this->genreService->delete((int)$id);

            header('Location: ../../views/admin/index.php?page=genres&delete=1');
            exit;
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=genres&error=' . urlencode($e->getMessage()));
            exit;
        }
    }
}