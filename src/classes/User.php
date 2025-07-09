<?php

namespace NL;

use PDO;

class User
{
    private ?PDO $db;

    public int $id = -1;
    public $username;
    public $password;
    public $email;
    public $created_at;
    public $role;
    public $avatar;

    public function __construct(?PDO $pdo)
    {
        $this->db = $pdo;
    }

    public function fill(array $data): User
    {
        $this->username = $data['username'] ?? '';
        $this->password = $data['password'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->role = $data['role'] ?? 'customer';
        $this->avatar = $data['avatar'] ?? '';
        return $this;
    }

    protected array $errors = [];

    public function validate(array $data): array
    {
        if (empty($data['username'])) {
            $this->errors['username'] = 'Tên là bắt buộc.';
        } elseif (strlen($data['username']) < 3) {
            $this->errors['username'] = 'Tên phải có ít nhất 3 ký tự.';
        }

        if (empty($data['email'])) {
            $this->errors['email'] = 'Email là bắt buộc.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Email không hợp lệ.';
        }

        if (empty($data['password'])) {
            $this->errors['password'] = 'Mật khẩu là bắt buộc.';
        } elseif (strlen($data['password']) < 6) {
            $this->errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
        }

        return $this->errors;
    }

    // Hàm kiểm tra email đã tồn tại hay chưa
    public function emailExists(string $email): bool
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM User WHERE email = :email');
        $statement->execute(['email' => $email]);
        return $statement->fetchColumn() > 0;
    }

    public function all(): array
    {
        $User = [];
        $statement = $this->db->prepare('SELECT * FROM User');
        $statement->execute();
        while ($row = $statement->fetch()) {
            $contact = new User($this->db);
            $contact->fillFromDbRow($row);
            $User[] = $contact;
        }
        return $User;
    }

    protected function fillFromDbRow(array $row): User
    {
        $this->id = $row['id'];
        $this->username = $row['username'];
        $this->password = $row['password'];
        $this->email = $row['email'];
        $this->role = $row['role'];
        $this->created_at = $row['created_at'];
        $this->avatar = $row['avatar'];
        return $this;
    }

    public function count(): int
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM User');
        $statement->execute();
        return $statement->fetchColumn();
    }

    public function paginate(int $offset = 0, int $limit = 10): array
    {
        $User = [];
        $statement = $this->db->prepare('SELECT * FROM User LIMIT :offset,:limit');
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $contact = new User($this->db);
            $contact->fillFromDbRow($row);
            $User[] = $contact;
        }
        return $User;
    }

    public function save(): bool
    {
        $result = false;
        if ($this->id >= 0) {
            if (empty($this->avatar)) {
                $statement = $this->db->prepare(
                    'UPDATE User 
                SET username = :username, password = :password, email = :email, role = :role 
                WHERE id = :id'
                );
                $result = $statement->execute([
                    'username' => $this->username,
                    'password' => $this->password,
                    'email' => $this->email,
                    'role' => $this->role,
                    'id' => $this->id
                ]);
            } else {
                $statement = $this->db->prepare(
                    'UPDATE User 
                SET username = :username, password = :password, email = :email, role = :role, avatar = :avatar 
                WHERE id = :id'
                );
                $result = $statement->execute([
                    'username' => $this->username,
                    'password' => $this->password,
                    'email' => $this->email,
                    'role' => $this->role,
                    'avatar' => $this->avatar,
                    'id' => $this->id
                ]);
            }
        } else {
            // Gán giá trị mặc định cho role là 'customer' nếu không có role được chỉ định
            $role = $this->role ?? 'customer';

            $statement = $this->db->prepare(
                'INSERT INTO User (username, password, email, role, avatar, created_at) 
             VALUES (:username, :password, :email, :role, :avatar, NOW())'
            );
            $result = $statement->execute([
                'username' => $this->username,
                'password' => password_hash($this->password, PASSWORD_DEFAULT),
                'email' => $this->email,
                'role' => $role,
                'avatar' => $this->avatar
            ]);

            if ($result) {
                $this->id = $this->db->lastInsertId();
            }
        }
        return $result;
    }

    public function find(int $id): ?User
    {
        $statement = $this->db->prepare('select * from User where id = :id');
        $statement->execute(['id' => $id]);
        if ($row = $statement->fetch()) {
            $this->fillFromDbRow($row);
            return $this;
        }
        return null;
    }
    public function findByEmailOrUsername($emailOrUsername)
    {
        $stmt = $this->db->prepare("SELECT * FROM user WHERE email = :emailOrUsername OR username = :emailOrUsername LIMIT 1");
        $stmt->execute([':emailOrUsername' => $emailOrUsername]);
        return $stmt->fetchObject();
    }


    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM user");
        $stmt->setFetchMode(\PDO::FETCH_OBJ);
        return $stmt->fetchAll();
    }

    // Lấy chi tiết người dùng theo ID
    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM user WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    // Thêm người dùng mới
    public function create($data)
    {
        $stmt = $this->db->prepare("INSERT INTO user (username, email, password, role) VALUES (:username, :email, :password, :role)");
        return $stmt->execute($data);
    }

    // Sửa thông tin người dùng
    public function update($id, $data)
    {
        $stmt = $this->db->prepare("UPDATE user SET username = :username, email = :email, role = :role WHERE id = :id");
        $data[':id'] = $id;
        return $stmt->execute($data);
    }

    // Xóa người dùng
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM user WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    public function updateProfile($id, $data)
{
    $sql = "UPDATE user SET username = :username, email = :email, gender = :gender";
    
    $params = [
        ':username' => $data['username'],
        ':email' => $data['email'],
        ':gender' => $data['gender'], // Thêm giới tính
        ':id' => $id
    ];

    if (!empty($data['password'])) {
        $sql .= ", password = :password";
        $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    }

    if (!empty($data['avatar'])) {
        $sql .= ", avatar = :avatar";
        $params[':avatar'] = $data['avatar'];
    }

    $sql .= " WHERE id = :id";

    $stmt = $this->db->prepare($sql);
    return $stmt->execute($params);
}

}
