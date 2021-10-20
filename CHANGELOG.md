# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
               
### Added

- Add calls to `\Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobRepositoryContract::start` and `\Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobRepositoryContract::finish` in `\Heptacom\HeptaConnect\Core\Job\Handler\EmissionHandler::triggerEmission`, `\Heptacom\HeptaConnect\Core\Job\Handler\ExplorationHandler::triggerExplorations` and `\Heptacom\HeptaConnect\Core\Job\Handler\ReceptionHandler::triggerReception` to track job states
- Add caching layer to `\Heptacom\HeptaConnect\Core\Configuration\ConfigurationService::getPortalNodeConfiguration`
- Add composer dependency `symfony/event-dispatcher: ^4.0 || ^5.0`
- Add log message `\Heptacom\HeptaConnect\Core\Component\LogMessage::MARK_AS_FAILED_ENTITY_IS_UNMAPPED` for issues during logging error messages during reception
- Add log message `\Heptacom\HeptaConnect\Core\Component\LogMessage::RECEIVE_NO_SAVE_MAPPINGS_NOT_PROCESSED` for issues after saving mappings after a reception
- Introduce `\Heptacom\HeptaConnect\Core\Event\PostReceptionEvent` for reception new event dispatcher in reception
- Add post-processing type `\Heptacom\HeptaConnect\Portal\Base\Reception\PostProcessing\MarkAsFailedData`
- Implement new method `\Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface::getEventDispatcher` in `\Heptacom\HeptaConnect\Core\Reception\ReceiveContext::getEventDispatcher`
- Implement new method `\Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface::getPostProcessingBag` in `\Heptacom\HeptaConnect\Core\Reception\ReceiveContext::getEventDispatcher`
- Add post-processor base class `\Heptacom\HeptaConnect\Core\Reception\Contract\PostProcessorContract`
- Add post-processing data bag class `\Heptacom\HeptaConnect\Core\Reception\Support\PostProcessorDataBag`
- Add post-processing for failed receptions using `\Heptacom\HeptaConnect\Core\Reception\PostProcessing\MarkAsFailedData` and handled in `\Heptacom\HeptaConnect\Core\Reception\PostProcessing\MarkAsFailedPostProcessor`
- Add post-processing for saving mappings after receptions using `\Heptacom\HeptaConnect\Core\Reception\PostProcessing\SaveMappingsData` and handled in `\Heptacom\HeptaConnect\Core\Reception\PostProcessing\SaveMappingsPostProcessor`
- Extract path building from `\Heptacom\HeptaConnect\Core\Storage\Normalizer\StreamNormalizer` and `\Heptacom\HeptaConnect\Core\Storage\Normalizer\StreamDenormalizer` into new service `\Heptacom\HeptaConnect\Core\Storage\Contract\StreamPathContract`

### Changed

- Change a parameter name of `\Heptacom\HeptaConnect\Core\Emission\EmitContext::markAsFailed` in global refactoring effort
- Change a parameter name of `\Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderFactoryInterface::createEmitterStackBuilder` in global refactoring effort, respective change in its implementing class `\Heptacom\HeptaConnect\Core\Emission\EmitterStackBuilderFactory::createEmitterStackBuilder`
- Change a parameter name of  `\Heptacom\HeptaConnect\Core\Emission\EmitterStackBuilder::__construct` in global refactoring effort and rename the field it is saved to. Change the fieldname in corresponding functions that use the field (`\Heptacom\HeptaConnect\Core\Emission\EmitterStackBuilder::push`, `\Heptacom\HeptaConnect\Core\Emission\EmitterStackBuilder::pushSource`, `\Heptacom\HeptaConnect\Core\Emission\EmitterStackBuilder::pushDecorators`)
- Change a parameter name of `\Heptacom\HeptaConnect\Core\Emission\EmitService::getEmitterStack` in global refactoring effort
- Change a parameter name of `\Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorerStackBuilderFactoryInterface::createExplorerStackBuilder` in global refactoring effort, respective change in its implementing class `\Heptacom\HeptaConnect\Core\Exploration\ExplorerStackBuilderFactory::createExplorerStackBuilder`
- Change a parameter name of `\Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorationActorInterface::performExploration` in global refactoring effort, respective change in its implementing class `\Heptacom\HeptaConnect\Core\Exploration\ExplorationActor::performExploration`
- Change a parameter name of `\Heptacom\HeptaConnect\Core\Exploration\ExplorerStackBuilder::__construct` in global refactoring effort and rename the field it is saved to. Change the fieldname in corresponding functions that use the field (`\Heptacom\HeptaConnect\Core\Exploration\ExplorerStackBuilder::push`, `\Heptacom\HeptaConnect\Core\Exploration\ExplorerStackBuilder::pushSource`, `\Heptacom\HeptaConnect\Core\Exploration\ExplorerStackBuilder::pushDecorators`)
- Change a parameter name of `\Heptacom\HeptaConnect\Core\Reception\Contract\ReceiverStackBuilderFactoryInterface::createReceiverStackBuilder` in global refactoring effort, respective change in its implementing class `\Heptacom\HeptaConnect\Core\Reception\ReceiverStackBuilderFactory::createReceiverStackBuilder`
- Change a parameter name of `\Heptacom\HeptaConnect\Core\Reception\ReceiverStackBuilder::__construct` in global refactoring effort and rename the field it is saved to. Change the fieldname in corresponding functions that use the field (`\Heptacom\HeptaConnect\Core\Reception\ReceiverStackBuilder::push`, `\Heptacom\HeptaConnect\Core\Reception\ReceiverStackBuilder::pushSource`, `\Heptacom\HeptaConnect\Core\Reception\ReceiverStackBuilder::pushDecorators`)
- Change a parameter name of `\Heptacom\HeptaConnect\Core\Reception\ReceiveService::getReceiverStack` in global refactoring effort
- Change a parameter name of `\Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface::get` and `\Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface::getListByExternalIds` in global refactoring effort, respective change in its implementing class for `\Heptacom\HeptaConnect\Core\Mapping\MappingService::get` and `\Heptacom\HeptaConnect\Core\Mapping\MappingService::getListByExternalIds` and additionally `\Heptacom\HeptaConnect\Core\Mapping\MappingService::ensurePersistence`
- Change a parameter name of `\Heptacom\HeptaConnect\Core\Mapping\MappingNodeStruct::__construct` in global refactoring effort and change its getter and setter methods to match the change (`\Heptacom\HeptaConnect\Core\Mapping\MappingNodeStruct::getEntityType`, `\Heptacom\HeptaConnect\Core\Mapping\MappingNodeStruct::setEntityType`)
- Change a parameter name of `\Heptacom\HeptaConnect\Core\Mapping\Publisher::publish` in global refactoring effort
- Change a parameter name of `\Heptacom\HeptaConnect\Core\Reception\Support\PrimaryKeyChangesAttachable::__construct` in global refactoring effort and change its getter method to match the change (`\Heptacom\HeptaConnect\Core\Reception\Support\PrimaryKeyChangesAttachable::getForeignEntityType`)
- Change method call in `\Heptacom\HeptaConnect\Core\Job\Handler\ReceptionHandler::triggerReception` to use renamed method of `\Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract`
- Change method call in  `\Heptacom\HeptaConnect\Core\Job\Handler\EmissionHandler::triggerEmission`, `\Heptacom\HeptaConnect\Core\Job\Handler\ExplorationHandler::triggerExplorations`, `\Heptacom\HeptaConnect\Core\Mapping\MappingService::ensurePersistence`, `\Heptacom\HeptaConnect\Core\Mapping\MappingService::reflect`, `\Heptacom\HeptaConnect\Core\Mapping\MappingService::merge` to use renamed method of `\Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract`
- Change method name of `\Heptacom\HeptaConnect\Core\Mapping\MappingStruct` in global refactoring effort
- Add dependency onto `\Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobRepositoryContract` into `\Heptacom\HeptaConnect\Core\Job\Handler\EmissionHandler`, `\Heptacom\HeptaConnect\Core\Job\Handler\ExplorationHandler` and `\Heptacom\HeptaConnect\Core\Job\Handler\ReceptionHandler` for job tracking
- Add dependency onto `\Psr\Cache\CacheItemPoolInterface` into `\Heptacom\HeptaConnect\Core\Configuration\ConfigurationService` for configuration caching
- Remove parameter `$mappingService` from `\Heptacom\HeptaConnect\Core\Reception\ReceiveContext::__construct` and `\Heptacom\HeptaConnect\Core\Reception\ReceiveContextFactory::__construct` as it is no longer needed
- Add parameter `$postProcessors` to `\Heptacom\HeptaConnect\Core\Reception\ReceiveContext::__construct` and `\Heptacom\HeptaConnect\Core\Reception\ReceiveContextFactory::__construct` to contain every post-processing handler for this context
- Change `\Heptacom\HeptaConnect\Core\Reception\ReceiveContext::markAsFailed` to add `\Heptacom\HeptaConnect\Portal\Base\Reception\PostProcessing\MarkAsFailedData` to the post-processing data bag instead of directly passing to `\Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface::addException`
- Remove parameter `$mappingPersister` from `\Heptacom\HeptaConnect\Core\Reception\ReceptionActor::__construct` as its usage has been moved into `\Heptacom\HeptaConnect\Core\Reception\PostProcessing\SaveMappingsPostProcessor`
- Move of saving mappings from `\Heptacom\HeptaConnect\Core\Reception\ReceptionActor::performReception` into `\Heptacom\HeptaConnect\Core\Reception\PostProcessing\SaveMappingsPostProcessor::handle`

### Deprecated

- Move `\Heptacom\HeptaConnect\Core\Storage\Normalizer\StreamNormalizer::STORAGE_LOCATION` into `\Heptacom\HeptaConnect\Core\Storage\Contract\StreamPathContract::STORAGE_LOCATION`

## [0.7.0] - 2021-09-25

### Added

- Change implementation for `\Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface` in `\Heptacom\HeptaConnect\Core\Portal\PortalStorage` to allow PSR simple cache compatibility
- Add log messages `1631387202`, `1631387363`, `1631387430`, `1631387448`, `1631387470`, `1631387510`, `1631561839`, `1631562097`, `1631562285`, `1631562928`, `1631563058`, `1631563639`, `1631563699`, `1631565257`, `1631565376`, `1631565446` to `\Heptacom\HeptaConnect\Core\Portal\PortalStorage`
- Add interface `\Heptacom\HeptaConnect\Core\Reception\Contract\ReceiveContextFactoryInterface` to `\Heptacom\HeptaConnect\Core\Reception\ReceiveContextFactory`
- Add interface `\Heptacom\HeptaConnect\Core\Job\Contract\ReceptionHandlerInterface` to `\Heptacom\HeptaConnect\Core\Job\Handler\ReceptionHandler`
- Add interface `\Heptacom\HeptaConnect\Core\Job\Contract\ExplorationHandlerInterface` to `\Heptacom\HeptaConnect\Core\Job\Handler\ExplorationHandler`
- Add interface `\Heptacom\HeptaConnect\Core\Job\Contract\EmissionHandlerInterface` to `\Heptacom\HeptaConnect\Core\Job\Handler\EmissionHandler`
- Add interface `\Heptacom\HeptaConnect\Core\Emission\Contract\EmitContextFactoryInterface` to `\Heptacom\HeptaConnect\Core\Emission\EmitContextFactory`
- Add method `\Heptacom\HeptaConnect\Core\Exploration\DirectEmitter::batch` for better performance in direct emissions

### Changed

- `\Heptacom\HeptaConnect\Core\Portal\PortalStorage::get` and `\Heptacom\HeptaConnect\Core\Portal\PortalStorage::set` will now throw exceptions when normalization could not happen
- Add parameter for `\Psr\Log\LoggerInterface` dependency in `\Heptacom\HeptaConnect\Core\Portal\PortalStorage::__construct` and `\Heptacom\HeptaConnect\Core\Portal\PortalStorageFactory::__construct`
- Change type of parameter `\Heptacom\HeptaConnect\Core\Reception\ReceiveContextFactory` to its new interface `\Heptacom\HeptaConnect\Core\Reception\Contract\ReceiveContextFactoryInterface` in `\Heptacom\HeptaConnect\Core\Reception\ReceiveService::__construct`
- Change type of parameter `\Heptacom\HeptaConnect\Core\Job\Handler\EmissionHandler` to its new interface `\Heptacom\HeptaConnect\Core\Job\Contract\EmissionHandlerInterface` in `\Heptacom\HeptaConnect\Core\Job\DelegatingJobActor::__construct`
- Change type of parameter `\Heptacom\HeptaConnect\Core\Job\Handler\ReceptionHandler` to its new interface `\Heptacom\HeptaConnect\Core\Job\Contract\ReceptionHandlerInterface` in `\Heptacom\HeptaConnect\Core\Job\DelegatingJobActor::__construct`
- Change type of parameter `\Heptacom\HeptaConnect\Core\Job\Handler\ExplorationHandler` to its new interface `\Heptacom\HeptaConnect\Core\Job\Contract\ExplorationHandlerInterface` in `\Heptacom\HeptaConnect\Core\Job\DelegatingJobActor::__construct`
- Change type of parameter `\Heptacom\HeptaConnect\Core\Emission\EmitContextFactory` to its new interface `\Heptacom\HeptaConnect\Core\Emission\Contract\EmitContextFactoryInterface` in `\Heptacom\HeptaConnect\Core\Emission\EmitService::__construct`
- Change behavior of service `\Heptacom\HeptaConnect\Core\Flow\DirectEmissionFlow\DirectEmissionFlow` to not create mappings anymore
- Remove parameter `\Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface` from `\Heptacom\HeptaConnect\Core\Flow\DirectEmissionFlow\DirectEmissionFlow::__construct`
- Change method `\Heptacom\HeptaConnect\Core\Reception\ReceptionActor::saveMappings` to use new service `\Heptacom\HeptaConnect\Storage\Base\MappingPersister\Contract\MappingPersisterContract`
- `\Heptacom\HeptaConnect\Core\Exploration\ExplorerStackBuilder::pushSource` and `\Heptacom\HeptaConnect\Core\Exploration\ExplorerStackBuilder::pushDecorators` don't push explorers onto the stack when they are already in the stack
- `\Heptacom\HeptaConnect\Core\Emission\EmitterStackBuilder::pushSource` and `\Heptacom\HeptaConnect\Core\Emission\EmitterStackBuilder::pushDecorators` don't push emitters onto the stack when they already in the stack
- `\Heptacom\HeptaConnect\Core\Reception\ReceiverStackBuilder::pushSource` and `\Heptacom\HeptaConnect\Core\Reception\ReceiverStackBuilder::pushDecorators` don't push receivers onto the stack when they already in the stack

### Removed

- Remove method `\Heptacom\HeptaConnect\Core\Exploration\DirectEmitter::run` as it became obsolete

## [0.6.0] - 2021-07-26

### Added

- Add `\Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreServiceInterface::dispatchExploreJob` to start an exploration as a job via `\Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract::dispatch`
- Add `\Heptacom\HeptaConnect\Core\Job\Handler\ExplorationHandler` to handle exploration jobs `\Heptacom\HeptaConnect\Core\Job\Type\Exploration`
- Add support for handling exploration jobs in `\Heptacom\HeptaConnect\Core\Job\DelegatingJobActor` with using `\Heptacom\HeptaConnect\Core\Job\Handler\ExplorationHandler`
- Add `\Psr\Http\Message\ResponseFactoryInterface` service to the portal containers in `\Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerBuilder` for better http and messaging PSR support for portal developers
- Add `\Psr\Http\Message\StreamFactoryInterface` service to the portal containers in `\Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerBuilder` for better http and messaging PSR support for portal developers

### Changed

- Direct emission and explorations create mappings via `\Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface::getListByExternalIds` on `\Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorationActorInterface::performExploration` when implemented by `\Heptacom\HeptaConnect\Core\Exploration\ExplorationActor::performExploration`

## [0.5.1] - 2021-07-13

### Fixed

- Remove impact of entity primary keys on lock keys in `\Heptacom\HeptaConnect\Core\Job\Handler\ReceptionHandler::triggerReception`

## [0.5.0] - 2021-07-11

### Added

- Add composer dependency `symfony/yaml: ^4.4|^5.0`
- Add base class `\Heptacom\HeptaConnect\Portal\Base\Flow\DirectEmission\DirectEmissionFlowContract` to `\Heptacom\HeptaConnect\Core\Flow\DirectEmissionFlow` to expose service for portals
- Add classes to hold job data for batch processing `\Heptacom\HeptaConnect\Core\Job\JobData` and `\Heptacom\HeptaConnect\Core\Job\JobDataCollection`
- Add class `\Heptacom\HeptaConnect\Core\Portal\PortalLogger` that can decorate any `\Psr\Log\LoggerInterface` to apply any additional message prefix and context to all logs
- Add `\Heptacom\HeptaConnect\Portal\Base\Publication\Contract\PublisherInterface` to portal node service container
- Add `\Heptacom\HeptaConnect\Portal\Base\Flow\DirectEmission\DirectEmissionFlowContract` to portal node service container

### Changed

- The acting to jobs in `\Heptacom\HeptaConnect\Core\Job\Contract\DelegatingJobActorContract::performJob` will now happen in batches in `\Heptacom\HeptaConnect\Core\Job\Contract\DelegatingJobActorContract::performJobs` and expects different parameters
- The trigger on emission jobs in `\Heptacom\HeptaConnect\Core\Job\Handler\EmissionHandler::triggerEmission` will now happen in batches and expects different parameters
- The trigger on reception jobs in `\Heptacom\HeptaConnect\Core\Job\Handler\ReceptionHandler::triggerReception` will now happen in batches and expects different parameters
- Change signature of `\Heptacom\HeptaConnect\Core\Reception\Contract\ReceptionActorInterface::performReception` to not rely on previously entities bound to `\Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface` objects
- Change signature of `\Heptacom\HeptaConnect\Core\Reception\ReceiveContext::markAsFailed` to not rely on previously entities bound to `\Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface` objects
- Do most of the business logic for reception in `\Heptacom\HeptaConnect\Core\Job\Handler\ReceptionHandler` to have job related logic less bound to reception processes in general

### Deprecated

- Deprecate cronjobs and therefore mark `\Heptacom\HeptaConnect\Core\Cronjob\CronjobContext`, `\Heptacom\HeptaConnect\Core\Cronjob\CronjobContextFactory`, `\Heptacom\HeptaConnect\Core\Cronjob\CronjobService` as internal
- Deprecate webhooks and therefore mark `\Heptacom\HeptaConnect\Core\Webhook\WebhookContext`, `\Heptacom\HeptaConnect\Core\Webhook\WebhookContextFactory`, `\Heptacom\HeptaConnect\Core\Webhook\WebhookService`, `\Heptacom\HeptaConnect\Core\Webhook\Contact\UrlProviderInterface` as internal

### Removed

- Move `\Heptacom\HeptaConnect\Core\Flow\DirectEmissionFlow\DirectEmissionResult` into the portal base package as `\Heptacom\HeptaConnect\Portal\Base\Flow\DirectEmission\DirectEmissionResult`
- Move `\Heptacom\HeptaConnect\Core\Flow\DirectEmissionFlow\Exception\UnidentifiedEntityException` into the portal base package as `\Heptacom\HeptaConnect\Portal\Base\Flow\DirectEmission\Exception\UnidentifiedEntityException`
- The handling of jobs in `\Heptacom\HeptaConnect\Core\Flow\MessageQueueFlow\MessageHandler::handleJob` does not republish failed jobs anymore. That feature will be added back again in a different form
- The trigger on emission jobs in `\Heptacom\HeptaConnect\Core\Job\Handler\EmissionHandler::triggerEmission` will no longer report back success
- The trigger on reception jobs in `\Heptacom\HeptaConnect\Core\Job\Handler\ReceptionHandler::triggerReception` will no longer report back success
- Remove automatically registered services in `\Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass\RemoveAutoPrototypedDefinitionsCompilerPass` that is based on `\Throwable`, `\Heptacom\HeptaConnect\Dataset\Base\Contract\AttachableInterface`, `\Heptacom\HeptaConnect\Dataset\Base\Contract\CollectionInterface` and `\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract`
