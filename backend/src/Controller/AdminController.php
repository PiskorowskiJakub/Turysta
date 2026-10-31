<?php
declare(strict_types=1);

namespace Turysta\Controller;

use PDO;
use Turysta\Auth\Session;
use Turysta\Game\AnnouncementService;
use Turysta\Http\Request;

/** Panel administratora: na razie zarzadzanie ogloszeniami. Kazda akcja wymaga roli admin. */
final class AdminController
{
    private AnnouncementService $announcements;

    public function __construct(PDO $pdo)
    {
        $this->announcements = new AnnouncementService($pdo);
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function announcements(Request $r): array
    {
        Session::requireAdmin($r);
        return [200, ['announcements' => $this->announcements->all()]];
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function createAnnouncement(Request $r): array
    {
        $admin = Session::requireAdmin($r);
        return [201, ['announcement' => $this->announcements->create((int) $admin['id'], $r->body)]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function updateAnnouncement(Request $r, array $params = []): array
    {
        Session::requireAdmin($r);
        return [200, ['announcement' => $this->announcements->update((int) ($params['id'] ?? 0), $r->body)]];
    }

    /**
     * @param array<string,string> $params
     * @return array{0:int,1:array<string,mixed>}
     */
    public function deleteAnnouncement(Request $r, array $params = []): array
    {
        Session::requireAdmin($r);
        $this->announcements->delete((int) ($params['id'] ?? 0));
        return [200, ['message' => 'Ogłoszenie usunięto.']];
    }
}
