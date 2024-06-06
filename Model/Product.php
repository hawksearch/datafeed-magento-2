<?php
/**
 * Copyright (c) 2024 Hawksearch (www.hawksearch.com) - All Rights Reserved
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */
declare(strict_types=1);

namespace HawkSearch\Datafeed\Model;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;

class Product
{
    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var AbstractType[]
     */
    private $compositeTypes;

    /**
     * @var Type
     */
    private $productType;

    /**
     * @var ProductResource
     */
    private ProductResource $productResource;

    /**
     * @var MetadataPool
     */
    private MetadataPool $metadataPool;

    /**
     * Product constructor.
     * @param ProductFactory $productFactory
     * @param Type $productType
     */
    public function __construct(
        ProductFactory $productFactory,
        Type $productType,
        ProductResource $productResource,
        MetadataPool $metadataPool
    ) {
        $this->productFactory = $productFactory;
        $this->productType = $productType;
        $this->productResource = $productResource;
        $this->metadataPool = $metadataPool;
    }

    /**
     * @return AbstractType[]|null
     */
    public function getCompositeTypes()
    {
        if ($this->compositeTypes === null) {
            $productMock = $this->productFactory->create();
            foreach ($this->productType->getCompositeTypes() as $typeId) {
                $productMock->setTypeId($typeId);
                $this->compositeTypes[$typeId] = $this->productType->factory($productMock);
            }
        }

        return $this->compositeTypes;
    }

    /**
     * @param array $ids
     * @return array
     * @deprecated
     */
    public function getParentProductIds(array $ids)
    {
        $parentsMap = $this->getParentsByChildMap($ids);
        $parentIds = [];
        foreach ($ids as $childId) {
            $parentIds = array_merge($parentIds, $parentsMap[$childId] ?? []);
        }

        return $parentIds;
    }

    /**
     * Get IDs of parent products by their child IDs.
     *
     * Returns a hash array where key is a child ID and values are identifiers of parent products
     * from the catalog_product_relation table.
     *
     * @param int[] $childIds
     * @return array
     * @throws Exception
     */
    public function getParentsByChildMap(array $childIds)
    {
        $connection = $this->productResource->getConnection();
        /** @var EntityMetadataInterface $metadata */
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $linkField = $metadata->getLinkField();

        $select = $connection->select()->from(
            ['relation' => $this->productResource->getTable('catalog_product_relation')],
            ['relation.child_id']
        )->join(
            ['e' => $this->productResource->getTable('catalog_product_entity')],
            'e.' . $linkField . ' = relation.parent_id',
            ['e.entity_id']
        )->where(
            'relation.child_id IN(?)',
            $childIds
        );

        $rows = $connection->fetchAll($select);

        $map = [];
        foreach ($rows as $row) {
            $map[$row['child_id']] = $map[$row['child_id']] ?? [];
            $map[$row['child_id']][] = $row['entity_id'];
        }

        return $map;
    }
}
