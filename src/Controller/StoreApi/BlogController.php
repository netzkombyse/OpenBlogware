<?php
declare(strict_types=1);

namespace Werkl\OpenBlogware\Controller\StoreApi;

use OpenApi\Attributes as OAT;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;


#[Route(defaults: ['_routeScope' => ['store-api']])]
class BlogController extends AbstractBlogController
{
    /**
     * @var EntityRepository
     */
    private $blogRepository;
    private EntityRepository $seoUrlRepository;

    public function __construct(EntityRepository $blogRepository, EntityRepository $seoUrlRepository)
    {
        $this->blogRepository = $blogRepository;
        $this->seoUrlRepository = $seoUrlRepository;
    }

    public function getDecorated(): AbstractBlogController
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/blog', name: 'store-api.werkl_blog.load', methods: ['GET', 'POST'], defaults: ['_entity' => 'werkl_blog_entries'])]
    #[OAT\Get(
        path: '/store-api/blog',
        summary: 'This route can be used to load the werkl_blog_entries by specific filters',
        operationId: 'listBlog',
        tags: ['Store API', 'Blog'],
        parameters: [
            new OAT\Parameter(name: 'Api-Basic-Parameters'),
        ],
        responses: [
            new OAT\Response(
                response: '200',
                description: '',
                content: new OAT\JsonContent(
                    type: 'object',
                    properties: [
                        new OAT\Property(
                            property: 'total',
                            type: 'integer',
                            description: 'Total amount',
                        ),
                        new OAT\Property(
                            property: 'aggregations',
                            type: 'object',
                            description: 'aggregation result',
                        ),
                        new OAT\Property(
                            property: 'elements',
                            type: 'array',
                            items: new OAT\Items(ref: '#/components/schemas/blog_entities_flat'),
                        ),
                    ]
                ),
            ),
        ],
    )]
    public function load(Request $request, Criteria $criteria, SalesChannelContext $context): BlogControllerResponse
    {
        // Kriterien für Blogeinträge aufbauen
        $criteria = $this->buildCriteria($request, $criteria);
    
        // Blogeinträge abrufen
        $blogEntries = $this->blogRepository->search($criteria, $context->getContext());
    
        // IDs der Blogeinträge sammeln
        $blogIds = $blogEntries->getIds();
    
        if (!empty($blogIds)) {
            // SEO-URLs für alle Blogeinträge in einer Abfrage laden
            $seoCriteria = new Criteria();
            $seoCriteria->addFilter(new EqualsAnyFilter('foreignKey', $blogIds)); // Alle Blog-IDs
            $seoCriteria->addFilter(new EqualsFilter('routeName', 'werkl.frontend.blog.detail')); // Filter für die Route
            $seoCriteria->addFilter(new EqualsFilter('isCanonical', true)); // Optional: nur kanonische URLs
    
            $seoUrls = $this->seoUrlRepository->search($seoCriteria, $context->getContext());
    
            // SEO-URLs nach Blog-ID gruppieren
            $seoUrlsByBlogId = [];
            foreach ($seoUrls as $seoUrl) {
                $blogId = $seoUrl->getForeignKey();
                $seoUrlsByBlogId[$blogId][] = $seoUrl;
            }
    
            // SEO-URLs den Blogeinträgen hinzufügen
            foreach ($blogEntries as $blogEntry) {
                $blogId = $blogEntry->getId();
            
                // SEO-URLs in eine ArrayStruct umwandeln
                $seoUrlsStruct = new ArrayStruct($seoUrlsByBlogId[$blogId] ?? []);
            
                // Erweiterung hinzufügen
                $blogEntry->addExtension('seoUrls', $seoUrlsStruct);
            }
        }
    
        return new BlogControllerResponse($blogEntries);
    }

    protected function buildCriteria(Request $request, Criteria $criteria): Criteria
    {
        $search = $request->get('search');
        if ($search) {
            if (Uuid::isValid($search)) {
                $criteria->setIds([$search]);
            } else {
                $criteria->addFilter(new EqualsFilter('slug', $search));
            }
        }

        $criteria->addAssociations(['blogAuthor.salutation','blogCategories','tags','cmsPage.sections','cmsPage.sections.blocks','cmsPage.sections.blocks.slots']);
        /*$criteria->getAssociation('seoUrls')->addFilter(
            new EqualsFilter('routeName', 'werkl.frontend.blog.detail')
        );*/

        return $criteria;
    }
}
