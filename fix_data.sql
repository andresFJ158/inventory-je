SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE products;
TRUNCATE TABLE raw_materials;
TRUNCATE TABLE raw_material_entries;
TRUNCATE TABLE productions;
TRUNCATE TABLE recipes;
TRUNCATE TABLE recipe_ingredients;
TRUNCATE TABLE recipe_labor;
SET FOREIGN_KEY_CHECKS = 1;

UPDATE pages SET title_page = 'Categorias' WHERE title_page = 'Categorías';
UPDATE pages SET title_page = 'Catalogo M.P.' WHERE title_page = 'Catálogo M.P.';
UPDATE pages SET title_page = 'Produccion' WHERE title_page = 'Producción';
