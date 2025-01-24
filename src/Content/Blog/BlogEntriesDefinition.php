<?php
declare(strict_types=1);

namespace Werkl\OpenBlogware\Content\Blog;

use Shopware\Core\Content\Cms\CmsPageDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField; // Import für OneToManyAssociationField
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Tag\TagDefinition;
use Werkl\OpenBlogware\Content\Blog\Aggregate\BlogCategoryMappingDefinition;
use Werkl\OpenBlogware\Content\Blog\BlogEntriesTranslation\BlogEntriesTranslationDefinition;
use Werkl\OpenBlogware\Content\BlogAuthor\BlogAuthorDefinition;
use Werkl\OpenBlogware\Content\BlogCategory\BlogCategoryDefinition;

class BlogEntriesDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'werkl_blog_entries';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return BlogEntriesEntity::class;
    }

    public function getCollectionClass(): string
    {
        return BlogEntriesCollection::class;
    }

    public function getDefaults(): array
    {
        return ['publishedAt' => new \DateTime()];
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey(), new ApiAware()),
            (new BoolField('active', 'active'))->addFlags(new ApiAware()),
            (new BoolField('detail_teaser_image', 'detailTeaserImage'))->addFlags(new ApiAware()),

            new FkField('media_id', 'mediaId', MediaDefinition::class),
            (new FkField('author_id', 'authorId', BlogAuthorDefinition::class))->addFlags(new Required()),
            (new FkField('cms_page_id', 'cmsPageId', CmsPageDefinition::class))->addFlags(new ApiAware(), new Inherited()),
            (new ReferenceVersionField(CmsPageDefinition::class))->addFlags(new PrimaryKey(), new Required()),

            (new OneToOneAssociationField('media', 'media_id', 'id', MediaDefinition::class, true))->addFlags(new ApiAware()),

            (new TranslatedField('title'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new TranslatedField('slug'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new TranslatedField('teaser'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new TranslatedField('metaTitle'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new TranslatedField('metaDescription'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::MIDDLE_SEARCH_RANKING)),
            (new TranslatedField('content'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::MIDDLE_SEARCH_RANKING)),
            (new TranslatedField('customFields'))->addFlags(new ApiAware()),

            (new DateField('published_at', 'publishedAt'))->addFlags(new Required(), new ApiAware()),

            (new TranslationsAssociationField(BlogEntriesTranslationDefinition::class, 'werkl_blog_entries_id'))->addFlags(new Required()),

            (new ManyToManyAssociationField('blogCategories', BlogCategoryDefinition::class, BlogCategoryMappingDefinition::class, 'werkl_blog_entries_id', 'werkl_blog_category_id'))->addFlags(new CascadeDelete(), new ApiAware(), new SearchRanking(SearchRanking::ASSOCIATION_SEARCH_RANKING)),
            (new ManyToOneAssociationField('blogAuthor', 'author_id', BlogAuthorDefinition::class, 'id', false))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::ASSOCIATION_SEARCH_RANKING)),
            (new OneToOneAssociationField('cmsPage', 'cms_page_id', 'id', CmsPageDefinition::class, false))->addFlags(new ApiAware()),
            (new ManyToManyAssociationField('tags', TagDefinition::class, 'werkl_blog_entries_tag', 'werkl_blog_entries_id', 'tag_id'))->addFlags(new ApiAware()),

            // SEO-URLs Assoziation hinzufügen
            (new OneToManyAssociationField(
                'seoUrls',                    // Name der Assoziation
                SeoUrlDefinition::class,      // Ziel-Definition
                'foreign_key',                // Fremdschlüssel in der SEO-URL-Tabelle
                'id'                          // ID der BlogEntries-Definition
            ))->addFlags(new ApiAware(), new CascadeDelete()),
        ]);
    }
}
