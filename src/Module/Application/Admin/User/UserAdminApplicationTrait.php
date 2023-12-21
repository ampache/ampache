<?php

declare(strict_types=1);

namespace Ampache\Module\Application\Admin\User;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Provides generic methods for re-use within the user-admin application context
 */
trait UserAdminApplicationTrait
{
    /**
     * Renders a simple confirmation dialogue
     *
     * @param callable(int): void $dialogCallback The custom dialogue renderer callback
     */
    public function showGenericUserConfirmation(
        ServerRequestInterface $request,
        callable $dialogCallback
    ): ?ResponseInterface {
        $this->ui->showHeader();

        $userId = (int) ($request->getQueryParams()['user_id'] ?? 0);

        if ($userId < 1) {
            echo T_('You have requested an object that does not exist');
        } else {
            $dialogCallback($userId);
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
