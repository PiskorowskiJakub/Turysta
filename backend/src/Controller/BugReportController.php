<?php
declare(strict_types=1);

namespace Turysta\Controller;

use Turysta\Auth\Session;
use Turysta\Http\Request;
use Turysta\Support\BugReportService;

final class BugReportController
{
    public function __construct(private BugReportService $reports)
    {
    }

    /** @return array{0:int,1:array<string,mixed>} */
    public function create(Request $r): array
    {
        $user = Session::requireUser($r);
        $id = $this->reports->create($user, $r->body, $r->ip(), $r->header('user-agent'));
        return [201, ['id' => $id, 'message' => 'Dziękujemy! Zgłoszenie dotarło do administratora.']];
    }
}
