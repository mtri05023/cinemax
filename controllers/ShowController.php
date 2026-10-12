<?php
require_once __DIR__ . '/../services/ShowService.php';

class ShowController
{
    private ShowService $showService;

    public function __construct(ShowService $showService)
    {
        $this->showService = $showService;
    }

    public function getAllShows()
    {
        try {
            return $this->showService->getAllShows();
        } catch (Exception $e) {
            header('Location: index.php?page=shows&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function getShowById($id)
    {
        try {
            return $this->showService->getShowById($id);
        } catch (Exception $e) {
            header('Location: index.php?page=shows&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function getShowsByMovieId($movie_id)
    {
        try {
            return $this->showService->getShowsByMovieId($movie_id);
        } catch (Exception $e) {
            header('Location: index.php?page=shows&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function createShow()
    {
        try {
            $this->showService->createShow($_POST['movie_id'], $_POST['hall_id'], $_POST['show_date'], $_POST['start_time'], $_POST['end_time'], $_POST['base_price']);
            header('Location: ../../views/admin/index.php?page=shows&add=1');
            exit;
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=shows&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function updateShow($id)
    {
        try {
            $this->showService->updateShow($id, $_POST['movie_id'], $_POST['hall_id'], $_POST['show_date'], $_POST['start_time'], $_POST['end_time'], $_POST['base_price'], $_POST['status']);
            header('Location: ../../views/admin/index.php?page=shows&update=1');
            exit;
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=shows&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function deleteShow($id)
    {
        try {
            $this->showService->deleteShow($id);
            header('Location: ../../views/admin/index.php?page=shows&delete=1');
            exit;
        } catch (Exception $e) {
            header('Location: ../../views/admin/index.php?page=shows&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function getAllHalls_CinemasByShow()
    {
        try {
            return $this->showService->getAllHalls_CinemasByShow();
        } catch (Exception $e) {
            header('Location: index.php?page=shows&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function getTicketByShowId($show_id)
    {
        try {
            return $this->showService->getTicketByShowId($show_id);
        } catch (Exception $e) {
            header('Location: index.php?page=seat_selection&error=' . urlencode($e->getMessage()));
            exit;
        }
    }
}
