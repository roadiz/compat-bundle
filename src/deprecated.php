<?php
declare(strict_types=1);

class_alias(Symfony\Component\HttpKernel\Kernel::class, '\RZ\Roadiz\Core\Kernel');

class_alias(\Symfony\Component\DependencyInjection\Container::class, '\Pimple\Container');
class_alias(\RZ\Roadiz\CompatBundle\Controller\AppController::class, '\RZ\Roadiz\CMS\Controllers\AppController');
class_alias(\RZ\Roadiz\CompatBundle\Controller\BackendController::class, '\RZ\Roadiz\CMS\Controllers\BackendController');
class_alias(\RZ\Roadiz\CompatBundle\Controller\Controller::class, '\RZ\Roadiz\CMS\Controllers\Controller');
class_alias(\RZ\Roadiz\CompatBundle\Security\FirewallEntry::class, '\RZ\Roadiz\Utils\Security\FirewallEntry');
class_alias(\RZ\Roadiz\CompatBundle\EventSubscriber\CachableResponseSubscriber::class, '\RZ\Roadiz\Core\Events\CachableResponseSubscriber');

class_alias(\RZ\Roadiz\CoreBundle\Bag\Settings::class, '\RZ\Roadiz\Core\Bags\Settings');
class_alias(\RZ\Roadiz\CoreBundle\Controller\LoginRequestTrait::class, '\RZ\Roadiz\CMS\Traits\LoginRequestTrait');
class_alias(\RZ\Roadiz\CoreBundle\Controller\LoginResetTrait::class, '\RZ\Roadiz\CMS\Traits\LoginResetTrait');
class_alias(\RZ\Roadiz\CoreBundle\EntityHandler\NodeHandler::class, '\RZ\Roadiz\Core\Handlers\NodeHandler');
class_alias(\RZ\Roadiz\CoreBundle\EntityHandler\NodesSourcesHandler::class, '\RZ\Roadiz\Core\Handlers\NodesSourcesHandler');
class_alias(\RZ\Roadiz\CoreBundle\Exception\ForceResponseException::class, '\RZ\Roadiz\Core\Exceptions\ForceResponseException');
class_alias(\RZ\Roadiz\CoreBundle\Exception\NoTranslationAvailableException::class, '\RZ\Roadiz\Core\Exceptions\NoTranslationAvailableException');
class_alias(\RZ\Roadiz\CoreBundle\ListManager\EntityListManager::class, '\RZ\Roadiz\Core\ListManagers\EntityListManager');
class_alias(\RZ\Roadiz\CoreBundle\ListManager\EntityListManagerInterface::class, '\RZ\Roadiz\Core\ListManagers\EntityListManagerInterface');
class_alias(\RZ\Roadiz\CoreBundle\ListManager\Paginator::class, '\RZ\Roadiz\Core\ListManagers\Paginator');
class_alias(\RZ\Roadiz\CoreBundle\Mailer\ContactFormManager::class, '\RZ\Roadiz\Utils\ContactFormManager');
class_alias(\RZ\Roadiz\CoreBundle\Mailer\EmailManager::class, '\RZ\Roadiz\Utils\EmailManager');
class_alias(\RZ\Roadiz\CoreBundle\Node\NodeDuplicator::class, '\RZ\Roadiz\Utils\Node\NodeDuplicator');
class_alias(\RZ\Roadiz\CoreBundle\Node\NodeMover::class, '\RZ\Roadiz\Utils\Node\NodeMover');
class_alias(\RZ\Roadiz\CoreBundle\Node\NodeNameChecker::class, '\RZ\Roadiz\Utils\Node\NodeNameChecker');
class_alias(\RZ\Roadiz\CoreBundle\Node\NodeTranstyper::class, '\RZ\Roadiz\Utils\Node\NodeTranstyper');
class_alias(\RZ\Roadiz\CoreBundle\Node\UniqueNodeGenerator::class, '\RZ\Roadiz\Utils\Node\UniqueNodeGenerator');
class_alias(\RZ\Roadiz\CoreBundle\Node\UniversalDataDuplicator::class, '\RZ\Roadiz\Utils\Node\UniversalDataDuplicator');
class_alias(\RZ\Roadiz\CoreBundle\Preview\PreviewResolverInterface::class, '\RZ\Roadiz\Preview\PreviewResolverInterface');
class_alias(\RZ\Roadiz\CoreBundle\Repository\NodeRepository::class, '\RZ\Roadiz\Core\Repositories\NodeRepository');
class_alias(\RZ\Roadiz\CoreBundle\Repository\TranslationRepository::class, '\RZ\Roadiz\Core\Repositories\TranslationRepository');
class_alias(\RZ\Roadiz\CoreBundle\Routing\NodeRouteHelper::class, '\RZ\Roadiz\Core\Routing\NodeRouteHelper');
class_alias(\RZ\Roadiz\CoreBundle\Security\Authorization\Chroot\NodeChrootResolver::class, '\RZ\Roadiz\Core\Authorization\Chroot\NodeChrootResolver');
class_alias(\RZ\Roadiz\CoreBundle\Theme\ThemeResolverInterface::class, '\RZ\Roadiz\Utils\Theme\ThemeResolverInterface');

/*
 * Entities
 */
class_alias(\RZ\Roadiz\CoreBundle\Entity\Attribute::class, '\RZ\Roadiz\Core\Entities\Attribute');
class_alias(\RZ\Roadiz\CoreBundle\Entity\AttributeDocuments::class, '\RZ\Roadiz\Core\Entities\AttributeDocuments');
class_alias(\RZ\Roadiz\CoreBundle\Entity\AttributeGroup::class, '\RZ\Roadiz\Core\Entities\AttributeGroup');
class_alias(\RZ\Roadiz\CoreBundle\Entity\AttributeGroupTranslation::class, '\RZ\Roadiz\Core\Entities\AttributeGroupTranslation');
class_alias(\RZ\Roadiz\CoreBundle\Entity\AttributeTranslation::class, '\RZ\Roadiz\Core\Entities\AttributeTranslation');
class_alias(\RZ\Roadiz\CoreBundle\Entity\AttributeValue::class, '\RZ\Roadiz\Core\Entities\AttributeValue');
class_alias(\RZ\Roadiz\CoreBundle\Entity\AttributeValueTranslation::class, '\RZ\Roadiz\Core\Entities\AttributeValueTranslation');
class_alias(\RZ\Roadiz\CoreBundle\Entity\CustomForm::class, '\RZ\Roadiz\Core\Entities\CustomForm');
class_alias(\RZ\Roadiz\CoreBundle\Entity\CustomFormAnswer::class, '\RZ\Roadiz\Core\Entities\CustomFormAnswer');
class_alias(\RZ\Roadiz\CoreBundle\Entity\CustomFormField::class, '\RZ\Roadiz\Core\Entities\CustomFormField');
class_alias(\RZ\Roadiz\CoreBundle\Entity\CustomFormFieldAttribute::class, '\RZ\Roadiz\Core\Entities\CustomFormFieldAttribute');
class_alias(\RZ\Roadiz\CoreBundle\Entity\Document::class, '\RZ\Roadiz\Core\Entities\Document');
class_alias(\RZ\Roadiz\CoreBundle\Entity\DocumentTranslation::class, '\RZ\Roadiz\Core\Entities\DocumentTranslation');
class_alias(\RZ\Roadiz\CoreBundle\Entity\Folder::class, '\RZ\Roadiz\Core\Entities\Folder');
class_alias(\RZ\Roadiz\CoreBundle\Entity\FolderTranslation::class, '\RZ\Roadiz\Core\Entities\FolderTranslation');
class_alias(\RZ\Roadiz\CoreBundle\Entity\Font::class, '\RZ\Roadiz\Core\Entities\Font');
class_alias(\RZ\Roadiz\CoreBundle\Entity\Group::class, '\RZ\Roadiz\Core\Entities\Group');
class_alias(\RZ\Roadiz\CoreBundle\Entity\Log::class, '\RZ\Roadiz\Core\Entities\Log');
class_alias(\RZ\Roadiz\CoreBundle\Entity\LoginAttempt::class, '\RZ\Roadiz\Core\Entities\LoginAttempt');
class_alias(\RZ\Roadiz\CoreBundle\Entity\Node::class, '\RZ\Roadiz\Core\Entities\Node');
class_alias(\RZ\Roadiz\CoreBundle\Entity\NodesCustomForms::class, '\RZ\Roadiz\Core\Entities\NodesCustomForms');
class_alias(\RZ\Roadiz\CoreBundle\Entity\NodesSources::class, '\RZ\Roadiz\Core\Entities\NodesSources');
class_alias(\RZ\Roadiz\CoreBundle\Entity\NodesSourcesDocuments::class, '\RZ\Roadiz\Core\Entities\NodesSourcesDocuments');
class_alias(\RZ\Roadiz\CoreBundle\Entity\NodesToNodes::class, '\RZ\Roadiz\Core\Entities\NodesToNodes');
class_alias(\RZ\Roadiz\CoreBundle\Entity\NodeType::class, '\RZ\Roadiz\Core\Entities\NodeType');
class_alias(\RZ\Roadiz\CoreBundle\Entity\NodeTypeField::class, '\RZ\Roadiz\Core\Entities\NodeTypeField');
class_alias(\RZ\Roadiz\CoreBundle\Entity\Redirection::class, '\RZ\Roadiz\Core\Entities\Redirection');
class_alias(\RZ\Roadiz\CoreBundle\Entity\Role::class, '\RZ\Roadiz\Core\Entities\Role');
class_alias(\RZ\Roadiz\CoreBundle\Entity\Setting::class, '\RZ\Roadiz\Core\Entities\Setting');
class_alias(\RZ\Roadiz\CoreBundle\Entity\SettingGroup::class, '\RZ\Roadiz\Core\Entities\SettingGroup');
class_alias(\RZ\Roadiz\CoreBundle\Entity\Tag::class, '\RZ\Roadiz\Core\Entities\Tag');
class_alias(\RZ\Roadiz\CoreBundle\Entity\TagTranslation::class, '\RZ\Roadiz\Core\Entities\TagTranslation');
class_alias(\RZ\Roadiz\CoreBundle\Entity\TagTranslationDocuments::class, '\RZ\Roadiz\Core\Entities\TagTranslationDocuments');
class_alias(\RZ\Roadiz\CoreBundle\Entity\Theme::class, '\RZ\Roadiz\Core\Entities\Theme');
class_alias(\RZ\Roadiz\CoreBundle\Entity\Translation::class, '\RZ\Roadiz\Core\Entities\Translation');
class_alias(\RZ\Roadiz\CoreBundle\Entity\UrlAlias::class, '\RZ\Roadiz\Core\Entities\UrlAlias');
class_alias(\RZ\Roadiz\CoreBundle\Entity\User::class, '\RZ\Roadiz\Core\Entities\User');
class_alias(\RZ\Roadiz\CoreBundle\Entity\UserLogEntry::class, '\RZ\Roadiz\Core\Entities\UserLogEntry');
