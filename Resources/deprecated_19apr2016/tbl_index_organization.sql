DROP TABLE IF EXISTS `tbl_index_organization`;
CREATE TABLE `tbl_index_organization` AS
SELECT Listing.id,Listing.account_id,Listing.title as name,Listing.keywords,Listing.url,Listing.description,TRIM(CONCAT(Listing.address,' ',Listing.address2)) AS street_address,Listing.postcode_suburb_id,Listing.city AS suburb,Listing.region,Listing.state,Listing.zip_code AS post,Listing.avg_review,Listing.count_review,Listing.number_views as count_visit,GROUP_CONCAT(ListingCategory.id) AS category_id,GROUP_CONCAT(ListingCategory.page_title) AS category_name,GROUP_CONCAT(ListingCategory.keywords) AS category_keywords,IF((CURDATE()<=ListingFeatured.date_end AND CURDATE()>=ListingFeatured.date_start), 1, 0) AS featured,Listing.status FROM Listing Listing
LEFT JOIN Listing_Category Listing_Category ON Listing.id = Listing_Category.listing_id
LEFT JOIN ListingCategory ListingCategory ON ListingCategory.id = Listing_Category.category_id
LEFT JOIN ListingFeatured ON Listing.id = ListingFeatured.listing_id
GROUP BY Listing.id;
ALTER TABLE `tbl_index_organization` ADD PRIMARY KEY(`id`);
ALTER TABLE `tbl_index_organization` ENGINE = MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE `tbl_index_organization` ADD FULLTEXT KEY fulltext_category (category_name, category_keywords);
ALTER TABLE `tbl_index_organization` ADD FULLTEXT KEY fulltext_keyword (name, description, keywords);
ALTER TABLE `tbl_index_organization` ADD FULLTEXT KEY fulltext_location (street_address, suburb, region, state);