<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Router\Contract;

use Heptacom\HeptaConnect\Core\Component\Messenger\Message\BatchPublishMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\EmitMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\PublishMessage;

interface RouterInterface
{
    public function handlePublishMessage(PublishMessage $message): void;

    public function handleBatchPublishMessage(BatchPublishMessage $message): void;

    public function handleEmitMessage(EmitMessage $message): void;
}
