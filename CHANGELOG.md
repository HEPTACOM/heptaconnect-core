# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.6.0] - 2021-07-26

### Added

* Add `\Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreServiceInterface::dispatchExploreJob` to start an exploration as a job via `\Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract::dispatch`
* Add `\Heptacom\HeptaConnect\Core\Job\Handler\ExplorationHandler` to handle exploration jobs `\Heptacom\HeptaConnect\Core\Job\Type\Exploration`
* Add support for handling exploration jobs in `\Heptacom\HeptaConnect\Core\Job\DelegatingJobActor` with using `\Heptacom\HeptaConnect\Core\Job\Handler\ExplorationHandler`
* Add `\Psr\Http\Message\ResponseFactoryInterface` service to the portal containers in `\Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerBuilder` for better http and messaging PSR support for portal developers
* Add `\Psr\Http\Message\StreamFactoryInterface` service to the portal containers in `\Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerBuilder` for better http and messaging PSR support for portal developers

### Changed

* Direct emission and explorations create mappings via `\Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface::getListByExternalIds` on `\Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorationActorInterface::performExploration` when implemented by `\Heptacom\HeptaConnect\Core\Exploration\ExplorationActor::performExploration`

## [0.5.1] - 2021-07-13

### Fixed

* Removed impact of entity primary keys on lock keys in `\Heptacom\HeptaConnect\Core\Job\Handler\ReceptionHandler::triggerReception`

## [0.5.0] - 2021-07-11

### Added

* Add composer dependency `symfony/yaml: ^4.4|^5.0`
* Add base class `\Heptacom\HeptaConnect\Portal\Base\Flow\DirectEmission\DirectEmissionFlowContract` to `\Heptacom\HeptaConnect\Core\Flow\DirectEmissionFlow` to expose service for portals
* Add classes to hold job data for batch processing `\Heptacom\HeptaConnect\Core\Job\JobData` and `\Heptacom\HeptaConnect\Core\Job\JobDataCollection`
* Add class `\Heptacom\HeptaConnect\Core\Portal\PortalLogger` that can decorate any `\Psr\Log\LoggerInterface` to apply any additional message prefix and context to all logs
* Add `\Heptacom\HeptaConnect\Portal\Base\Publication\Contract\PublisherInterface` to portal node service container
* Add `\Heptacom\HeptaConnect\Portal\Base\Flow\DirectEmission\DirectEmissionFlowContract` to portal node service container

### Changed

* The acting to jobs in `\Heptacom\HeptaConnect\Core\Job\Contract\DelegatingJobActorContract::performJob` will now happen in batches in `\Heptacom\HeptaConnect\Core\Job\Contract\DelegatingJobActorContract::performJobs` and expects different parameters
* The trigger on emission jobs in `\Heptacom\HeptaConnect\Core\Job\Handler\EmissionHandler::triggerEmission` will now happen in batches and expects different parameters
* The trigger on reception jobs in `\Heptacom\HeptaConnect\Core\Job\Handler\ReceptionHandler::triggerReception` will now happen in batches and expects different parameters
* Changed signature of `\Heptacom\HeptaConnect\Core\Reception\Contract\ReceptionActorInterface::performReception` to not rely on previously entities bound to `\Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface` objects
* Changed signature of `\Heptacom\HeptaConnect\Core\Reception\ReceiveContext::markAsFailed` to not rely on previously entities bound to `\Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface` objects
* Do most of the business logic for reception in `\Heptacom\HeptaConnect\Core\Job\Handler\ReceptionHandler` to have job related logic less bound to reception processes in general

### Deprecated

* Deprecated cronjobs and therefore marked `\Heptacom\HeptaConnect\Core\Cronjob\CronjobContext`, `\Heptacom\HeptaConnect\Core\Cronjob\CronjobContextFactory`, `\Heptacom\HeptaConnect\Core\Cronjob\CronjobService` as internal
* Deprecated webhooks and therefore marked `\Heptacom\HeptaConnect\Core\Webhook\WebhookContext`, `\Heptacom\HeptaConnect\Core\Webhook\WebhookContextFactory`, `\Heptacom\HeptaConnect\Core\Webhook\WebhookService`, `\Heptacom\HeptaConnect\Core\Webhook\Contact\UrlProviderInterface` as internal

### Removed

* Moved `\Heptacom\HeptaConnect\Core\Flow\DirectEmissionFlow\DirectEmissionResult` into the portal base package as `\Heptacom\HeptaConnect\Portal\Base\Flow\DirectEmission\DirectEmissionResult`
* Moved `\Heptacom\HeptaConnect\Core\Flow\DirectEmissionFlow\Exception\UnidentifiedEntityException` into the portal base package as `\Heptacom\HeptaConnect\Portal\Base\Flow\DirectEmission\Exception\UnidentifiedEntityException`
* The handling of jobs in `\Heptacom\HeptaConnect\Core\Flow\MessageQueueFlow\MessageHandler::handleJob` does not republish failed jobs anymore. That feature will be added back again in a different form
* The trigger on emission jobs in `\Heptacom\HeptaConnect\Core\Job\Handler\EmissionHandler::triggerEmission` will no longer report back success
* The trigger on reception jobs in `\Heptacom\HeptaConnect\Core\Job\Handler\ReceptionHandler::triggerReception` will no longer report back success
* Remove automatically registered services in `\Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass\RemoveAutoPrototypedDefinitionsCompilerPass` that is based on `\Throwable`, `\Heptacom\HeptaConnect\Dataset\Base\Contract\AttachableInterface`, `\Heptacom\HeptaConnect\Dataset\Base\Contract\CollectionInterface` and `\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract`