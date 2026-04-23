<?php
namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Session;
use App\Helpers\Formatter;
use App\Helpers\Upload;
use App\Models\Category;

class CategoryController extends AdminBaseController
{
    public function index(): void
    {
        $model = new Category();
        $this->view('categories/index', [
            'pageTitle'  => 'Categorías',
            'categories' => $model->withProductCount(),
        ]);
    }

    public function create(): void
    {
        $model = new Category();

        if ($this->isPost()) {
            Csrf::verifyOrFail();

            $name      = $this->input('name', '');
            $slug      = Formatter::slug($this->input('slug', '') ?: $name);
            $desc      = $this->input('description', '');
            $sortOrder = (int)$this->input('sort_order', 0);
            $active    = (int)$this->input('active', 1);

            $errors = [];
            if (strlen($name) < 2) $errors[] = 'El nombre es requerido.';
            if ($model->slugExists($slug)) $errors[] = 'El slug ya existe.';

            $image = null;
            if (!empty($_FILES['image']['name'])) {
                $upload = Upload::image($_FILES['image'], 'cat');
                if (!$upload['success']) {
                    $errors[] = $upload['error'];
                } else {
                    $image = $upload['filename'];
                }
            }

            if (!$errors) {
                $model->create(compact('name', 'slug', 'desc', 'image', 'sortOrder', 'active') + [
                    'description' => $desc,
                    'sort_order'  => $sortOrder,
                ]);
                Session::flash('success', 'Categoría creada correctamente.');
                $this->redirect('admin/categorias');
            }

            Session::flash('error', implode(' ', $errors));
        }

        $this->view('categories/form', [
            'pageTitle' => 'Nueva categoría',
            'category'  => null,
        ]);
    }

    public function edit(): void
    {
        $id    = (int)$this->input('id');
        $model = new Category();
        $cat   = $model->findById($id);

        if (!$cat) {
            Session::flash('error', 'Categoría no encontrada.');
            $this->redirect('admin/categorias');
        }

        if ($this->isPost()) {
            Csrf::verifyOrFail();

            $name      = $this->input('name', '');
            $slug      = Formatter::slug($this->input('slug', '') ?: $name);
            $desc      = $this->input('description', '');
            $sortOrder = (int)$this->input('sort_order', 0);
            $active    = (int)$this->input('active', 1);

            $errors = [];
            if (strlen($name) < 2) $errors[] = 'El nombre es requerido.';
            if ($model->slugExists($slug, $id)) $errors[] = 'El slug ya existe.';

            $image = $cat['image'];
            if (!empty($_FILES['image']['name'])) {
                $upload = Upload::image($_FILES['image'], 'cat');
                if (!$upload['success']) {
                    $errors[] = $upload['error'];
                } else {
                    if ($image) Upload::delete($image);
                    $image = $upload['filename'];
                }
            }

            if (!$errors) {
                $model->update($id, [
                    'name'        => $name,
                    'slug'        => $slug,
                    'description' => $desc,
                    'image'       => $image,
                    'sort_order'  => $sortOrder,
                    'active'      => $active,
                ]);
                Session::flash('success', 'Categoría actualizada.');
                $this->redirect('admin/categorias');
            }

            Session::flash('error', implode(' ', $errors));
        }

        $this->view('categories/form', [
            'pageTitle' => 'Editar categoría',
            'category'  => $cat,
        ]);
    }

    public function delete(): void
    {
        Csrf::verifyOrFail();
        $id    = (int)$this->input('id');
        $model = new Category();
        $cat   = $model->findById($id);

        if ($cat) {
            if ($cat['image']) Upload::delete($cat['image']);
            $model->delete($id);
            Session::flash('success', 'Categoría eliminada.');
        }

        $this->redirect('admin/categorias');
    }

    public function reorder(): void
    {
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids)) {
            $this->json(['success' => false]);
        }

        $model = new Category();
        foreach ($ids as $order => $id) {
            $model->update((int)$id, ['sort_order' => (int)$order]);
        }
        $this->json(['success' => true]);
    }
}
