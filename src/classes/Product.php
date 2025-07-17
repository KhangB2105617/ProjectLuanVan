<?php
namespace NL;

use PDO;

class Product
{
    private ?PDO $db;

    public function __construct(?PDO $pdo)
    {
        $this->db = $pdo;
    }

    // Lấy tất cả sản phẩm
    public function getAll()
    {
        // Truy vấn SQL để lấy tất cả sản phẩm
        $stmt = $this->db->query("SELECT * FROM products");
        $stmt->setFetchMode(\PDO::FETCH_OBJ);
        return $stmt->fetchAll();
    }

    // Lấy chi tiết sản phẩm theo id
    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT); // Liên kết tham số id với câu lệnh SQL
        $stmt->execute();
        $stmt->setFetchMode(\PDO::FETCH_OBJ);
        return $stmt->fetch();
    }

    // Lấy danh mục sản phẩm
    public function getCategories()
    {
        // Truy vấn SQL để lấy danh sách danh mục sản phẩm
        $stmt = $this->db->prepare("SELECT DISTINCT category FROM products");
        $stmt->execute();
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        return $stmt->fetchAll();
    }

    // Lấy sản phẩm theo danh mục
    public function getProductsByCategory($category)
    {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE category = :category");
        $stmt->bindParam(':category', $category, \PDO::PARAM_STR); // Liên kết tham số category với câu lệnh SQL
        $stmt->execute();
        $stmt->setFetchMode(\PDO::FETCH_OBJ);
        return $stmt->fetchAll();
    }
    public function getProductsByIds($ids)
{
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $this->db->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    return $stmt->fetchAll(\PDO::FETCH_OBJ);
}
// Lấy danh sách thương hiệu
public function getBrands() {
    $stmt = $this->db->query("SELECT DISTINCT brand, brand_image FROM products WHERE brand IS NOT NULL");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lấy sản phẩm theo thương hiệu
public function getProductsByBrand($brand)
{
    $stmt = $this->db->prepare("SELECT * FROM products WHERE brand = :brand");
    $stmt->bindParam(':brand', $brand, \PDO::PARAM_STR);
    $stmt->execute();
    $stmt->setFetchMode(\PDO::FETCH_OBJ);
    return $stmt->fetchAll();
}

public function getAllSorted($sortOrder = null) {
    if ($sortOrder === 'asc') {
        $order = "ORDER BY price ASC";
    } elseif ($sortOrder === 'desc') {
        $order = "ORDER BY price DESC";
    } else {
        $order = "ORDER BY id ASC"; // Mặc định sắp xếp theo ID
    }

    $stmt = $this->db->query("SELECT * FROM products $order");
    $stmt->setFetchMode(PDO::FETCH_OBJ);
    return $stmt->fetchAll();
}


// Tạo sản phẩm mới
public function create($data)
{
    $sql = "INSERT INTO products (name, price, category, image, description, quantity, brand) 
            VALUES (:name, :price, :category, :image, :description, :quantity, :brand)";
    $stmt = $this->db->prepare($sql);

    return $stmt->execute([
        ':name' => $data[':name'],
        ':price' => $data[':price'],
        ':category' => $data[':category'],
        ':image' => $data[':image'],
        ':description' => $data[':description'],
        ':quantity' => $data[':quantity'],
        ':brand' => $data[':brand'],
    ]);
}

// Cập nhật thông tin sản phẩm
public function update($id, $data)
{
    $sql = "UPDATE products 
            SET name = :name, price = :price, category = :category, image = :image, description = :description, quantity = :quantity
            WHERE id = :id";
    $stmt = $this->db->prepare($sql);

    return $stmt->execute([
        ':id' => $id,
        ':name' => $data[':name'],
        ':price' => $data[':price'],
        ':category' => $data[':category'],
        ':image' => $data[':image'],
        ':description' => $data[':description'],
        ':quantity' => $data[':quantity'],
    ]);
}

// Xóa sản phẩm
public function delete($id)
{
    $sql = "DELETE FROM products WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(':id', $id, \PDO::PARAM_INT);

    return $stmt->execute();
}

function removeVietnameseTones($str)
{
    $str = mb_strtolower($str, 'UTF-8');
    $accents = [
        'a'=>'áàảãạăắằẳẵặâấầẩẫậ',
        'd'=>'đ',
        'e'=>'éèẻẽẹêếềểễệ',
        'i'=>'íìỉĩị',
        'o'=>'óòỏõọôốồổỗộơớờởỡợ',
        'u'=>'úùủũụưứừửữự',
        'y'=>'ýỳỷỹỵ'
    ];
    foreach ($accents as $nonAccent => $accentsGroup) {
        $str = preg_replace('/[' . $accentsGroup . ']/u', $nonAccent, $str);
    }
    return $str;
}

// Tìm kiếm sản phẩm
public function searchProducts($searchTerm)
{
    $normalized = $this->removeVietnameseTones($searchTerm);
    $normalized = mb_strtolower($normalized, 'UTF-8');

    $allProducts = $this->getAll();
    $results = [];

    foreach ($allProducts as $product) {
        $nameNorm = mb_strtolower($this->removeVietnameseTones($product->name), 'UTF-8');
        $descNorm = mb_strtolower($this->removeVietnameseTones($product->description), 'UTF-8');

        if (strpos($nameNorm, $normalized) !== false || strpos($descNorm, $normalized) !== false) {
            $results[] = $product;
        }
    }

    return $results;
}
}