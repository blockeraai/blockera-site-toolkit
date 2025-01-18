<?php

namespace BlockeraAI\SiteToolkit\Repositories;

class ProductRepository
{
	/**
	 * Get the product by id.
	 *
	 * @param int $id The product id.
	 * 
	 * @return \WP_POST|null The product data.
	 */
	public function getById(int $id): ?\WP_POST
	{
		return get_post($id);
	}

	/**
	 * Get the product variations.
	 *
	 * @param int $id The product id.
	 *
	 * @return array The product variations.
	 */
	public function getProductVariations(int $id): array
	{
		$product = wc_get_product($id);

		if (!$product || !$product instanceof \WC_Product_Variable) {
			return [];
		}

		$attributes = $product->get_variation_attributes();

		return $attributes['Plan'] ?? [];
	}
}
