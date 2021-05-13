<?php
/**
 * Copyright (c) 2021 Hawksearch (www.hawksearch.com) - All Rights Reserved
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

namespace HawkSearch\Datafeed\Model\Product;

class AttributeFeedService
{
    /**#@+
     * Constants
     */
    const FEED_ITEMS_COLUMNS = [
        'product_id',
        'unique_id',
        'name',
        'url_detail',
        'image',
        'price_retail',
        'price_sale',
        'price_special',
        'price_special_from_date',
        'price_special_to_date',
        'group_id',
        'description_short',
        'description_long',
        'brand',
        'sku',
        'sort_default',
        'sort_rating',
        'is_free_shipping',
        'is_new',
        'is_on_sale',
        'keyword',
        'metric_inventory',
        'minimal_price',
        'type_id'
    ];
    const FEED_ATTRIBUTES_COLUMNS = [
        'unique_id',
        'key',
        'value'
    ];
    /**#@-*/

    /**
     * @return string[]
     */
    public function getPreconfiguredItemsColumns()
    {
        return static::FEED_ITEMS_COLUMNS;
    }

    /**
     * @return string[]
     */
    public function getPreconfiguredAttributesColumns()
    {
        return static::FEED_ATTRIBUTES_COLUMNS;
    }
}
