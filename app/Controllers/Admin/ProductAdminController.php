<?php
namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Session;
use App\Helpers\Formatter;
use App\Helpers\Upload;
use App\Models\Product;
use App\Models\Category;

class ProductAdminController extends AdminBaseController
{
    public function index(): void
    {
        $model      = new Product();
        $catModel   = new Category();
        $categoryId = (int)$this->input('categoria', 0);

        $this->view('products/index', [
            'pageTitle'  => 'Productos',
            'products'   => $model->withCategory(),
            'categories' => $catModel->allActive(),
            'filterCat'  => $categoryId,
        ]);
    }

    public function create(): void
    {
        $catModel = new Category();

        if ($this->isPost()) {
            Csrf::verifyOrFail();
            [$data, $errors] = $this->parseForm();

            if (!$errors) {
                $productModel = new Product();
                $id = $productModel->create($data);

                // Imágenes adicionales
                if (!empty($_FILES['gallery']['name'][0])) {
                    foreach ($_FILES['gallery']['tmp_name'] as $i => $tmpName) {
                        if ($_FILES['gallery']['error'][$i] === UPLOAD_ERR_OK) {
                            $file = [
                                'name'     => $_FILES['gallery']['name'][$i],
                                'tmp_name' => $tmpName,
                                'size'     => $_FILES['gallery']['size'][$i],
                                'error'    => $_FILES['gallery']['error'][$i],
                            ];
                            $up = Upload::image($file, 'gal');
                            if ($up['success']) {
                                $productModel->addImage($id, $up['filename']);
                            }
                        }
                    }
                }

                Session::flash('success', 'Producto creado correctamente.');
                $this->redirect('admin/productos');
            }

            Session::flash('error', implode(' ', $errors));
        }

        $this->view('products/form', [
            'pageTitle'  => 'Nuevo producto',
            'product'    => null,
            'images'     => [],
            'categories' => $catModel->allActive(),
        ]);
    }

    public function edit(): void
    {
        $id           = (int)$this->input('id');
        $productModel = new Product();
        $catModel     = new Category();
        $product      = $productModel->findById($id);

        if (!$product) {
            Session::flash('error', 'Producto no encontrado.');
            $this->redirect('admin/productos');
        }

        if ($this->isPost()) {
            Csrf::verifyOrFail();
            [$data, $errors] = $this->parseForm($id, $product);

            if (!$errors) {
                $productModel->update($id, $data);

                // Imágenes adicionales
                if (!empty($_FILES['gallery']['name'][0])) {
                    foreach ($_FILES['gallery']['tmp_name'] as $i => $tmpName) {
                        if ($_FILES['gallery']['error'][$i] === UPLOAD_ERR_OK) {
                            $file = [
                                'name'     => $_FILES['gallery']['name'][$i],
                                'tmp_name' => $tmpName,
                                'size'     => $_FILES['gallery']['size'][$i],
                                'error'    => $_FILES['gallery']['error'][$i],
                            ];
                            $up = Upload::image($file, 'gal');
                            if ($up['success']) {
                                $productModel->addImage($id, $up['filename']);
                            }
                        }
                    }
                }

                Session::flash('success', 'Producto actualizado.');
                $this->redirect('admin/productos');
            }

            Session::flash('error', implode(' ', $errors));
        }

        $this->view('products/form', [
            'pageTitle'  => 'Editar producto',
            'product'    => $product,
            'images'     => $productModel->getImages($id),
            'categories' => $catModel->allActive(),
        ]);
    }

    public function toggle(): void
    {
        $id    = (int)$this->input('id');
        $model = new Product();
        $model->toggle($id);
        $product = $model->findById($id);

        $this->json([
            'success'   => true,
            'available' => (int)$product['available'],
            'label'     => $product['available'] ? 'Disponible' : 'No disponible',
        ]);
    }

    public function delete(): void
    {
        Csrf::verifyOrFail();
        $id    = (int)$this->input('id');
        $model = new Product();
        $prod  = $model->findById($id);

        if ($prod) {
            if ($prod['main_image']) Upload::delete($prod['main_image']);
            foreach ($model->getImages($id) as $img) {
                Upload::delete($img['filename']);
            }
            $model->delete($id);
            Session::flash('success', 'Producto eliminado.');
        }

        $this->redirect('admin/productos');
    }

    public function deleteImage(): void
    {
        $imageId = (int)$this->input('id');
        $model   = new Product();
        $filename = $model->deleteImage($imageId);

        if ($filename) {
            Upload::delete($filename);
            $this->json(['success' => true]);
        }
        $this->json(['success' => false], 404);
    }

    private function parseForm(int $existingId = 0, array $existing = []): array
    {
        $name         = $this->input('name', '');
        $slug         = Formatter::slug($this->input('slug', '') ?: $name);
        $categoryId   = (int)$this->input('category_id', 0) ?: null;
        $shortDesc    = $this->input('short_desc', '');
        $description  = $this->input('description', '');
        $ingredients  = $this->input('ingredients', '');
        $price        = (float)str_replace(',', '.', $this->input('price', 0));
        $comparePrice = (float)str_replace(',', '.', $this->input('compare_price', 0));
        $featured     = (int)$this->input('featured', 0);
        $available    = (int)$this->input('available', 1);
        $sortOrder    = (int)$this->input('sort_order', 0);

        $errors  = [];
        $model   = new Product();

        if (strlen($name) < 2)    $errors[] = 'El nombre es requerido.';
        if ($price <= 0)          $errors[] = 'El precio debe ser mayor a 0.';
        if ($model->slugExists($slug, $existingId)) $errors[] = 'El slug ya existe.';

        // Imagen principal
        $mainImage = $existing['main_image'] ?? null;
        if (!empty($_FILES['main_image']['name'])) {
            $up = Upload::image($_FILES['main_image'], 'prod');
            if (!$up['success']) {
                $errors[] = $up['error'];
            } else {
                if ($mainImage) Upload::delete($mainImage);
                $mainImage = $up['filename'];
            }
        }

        $data = [
            'name'          => $name,
            'slug'          => $slug,
            'category_id'   => $categoryId,
            'short_desc'    => $shortDesc,
            'description'   => $description,
            'ingredients'   => $ingredients,
            'price'         => $price,
            'compare_price' => $comparePrice > 0 ? $comparePrice : null,
            'main_image'    => $mainImage,
            'featured'      => $featured,
            'available'     => $available,
            'sort_order'    => $sortOrder,
        ];

        return [$data, $errors];
    }
}
