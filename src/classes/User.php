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
    public $gender;
    public $address;
    public $phone;
    public $birthday;
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
        $this->gender = $row['gender'] ?? null;
        $this->address = $data['address'] ?? '';
        $this->phone = $data['phone'] ?? '';
        $this->birthday = $data['birthday'] ?? null;
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
        $statement = $this->db->prepare('SELECT COUNT(*) FROM Users WHERE email = :email');
        $statement->execute(['email' => $email]);
        return $statement->fetchColumn() > 0;
    }

    public function all(): array
    {
        $User = [];
        $statement = $this->db->prepare('SELECT * FROM Users');
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
        $this->gender = $row['gender'] ?? null;
        $this->address = $row['address'] ?? '';
        $this->phone = $row['phone'] ?? '';
        $this->birthday = $row['birthday'] ?? null;
        $this->role = $row['role'];
        $this->created_at = $row['created_at'];
        $this->avatar = $row['avatar'];

        return $this;
    }

    public function count(): int
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM Users');
        $statement->execute();
        return $statement->fetchColumn();
    }

    public function paginate(int $offset = 0, int $limit = 10): array
    {
        $User = [];
        $statement = $this->db->prepare('SELECT * FROM Users LIMIT :offset,:limit');
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
        if ($this->id >= 0) {
            $sql = 'UPDATE users SET username = :username, password = :password, email = :email, address = :address, phone = :phone, birthday = :birthday, role = :role';
            $params = [
                'username' => $this->username,
                'password' => $this->password,
                'email' => $this->email,
                'address' => $this->address,
                'phone' => $this->phone,
                'birthday' => $this->birthday,
                'role' => $this->role,
                'id' => $this->id
            ];

            if (!empty($this->avatar)) {
                $sql .= ', avatar = :avatar';
                $params['avatar'] = $this->avatar;
            }

            $sql .= ' WHERE id = :id';
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } else {
            $stmt = $this->db->prepare('
                INSERT INTO users (username, password, email, address, phone, birthday, role, avatar, created_at) 
                VALUES (:username, :password, :email, :address, :phone, :birthday, :role, :avatar, NOW())
            ');
            $result = $stmt->execute([
                'username' => $this->username,
                'password' => password_hash($this->password, PASSWORD_DEFAULT),
                'email' => $this->email,
                'address' => $this->address,
                'phone' => $this->phone,
                'birthday' => $this->birthday,
                'role' => $this->role,
                'avatar' => $this->avatar
            ]);
            if ($result) {
                $this->id = $this->db->lastInsertId();
            }
            return $result;
        }
    }

    public function find(int $id): ?User
    {
        $statement = $this->db->prepare('select * from Users where id = :id');
        $statement->execute(['id' => $id]);
        if ($row = $statement->fetch()) {
            $this->fillFromDbRow($row);
            return $this;
        }
        return null;
    }
    public function findByEmailOrUsername($emailOrUsername): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :emailOrUsername OR username = :emailOrUsername LIMIT 1");
        $stmt->execute([':emailOrUsername' => $emailOrUsername]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $user = new User($this->db);
            $user->fillFromDbRow($row);
            return $user;
        }

        return null;
    }
    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM users");
        $stmt->setFetchMode(\PDO::FETCH_OBJ);
        return $stmt->fetchAll();
    }

    // Lấy chi tiết người dùng theo ID
    public function getById($id): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            $user = new User($this->db);
            $user->fillFromDbRow($row);
            return $user;
        }

        return null;
    }

    // Thêm người dùng mới
    public function create($data)
    {
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)");
        return $stmt->execute($data);
    }

    // Sửa thông tin người dùng
    public function update($id, $data)
    {
        $sql = "UPDATE users SET username = :username, email = :email, role = :role, address = :address, phone = :phone, birthday = :birthday WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    // Xóa người dùng
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    public function updateProfile($id, $data)
    {
        $sql = "UPDATE users SET username = :username, email = :email, address = :address, phone = :phone, birthday = :birthday, gender = :gender";
        $params = [
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':address' => $data['address'],
            ':phone' => $data['phone'],
            ':birthday' => $data['birthday'] ?? null,
            ':gender' => $data['gender'] ?? null,
            ':id' => $id
        ];

        if (!empty($data['password'])) {
            $sql .= ", password = :password";
            $params[':password'] = ($data['password']);
        }

        if (!empty($data['avatar'])) {
            $sql .= ", avatar = :avatar";
            $params[':avatar'] = $data['avatar'];
        }

        $sql .= " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function getByUsername($username)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function updatePassword($id, $newPassword): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
        return $stmt->execute([
            ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
            ':id' => $id
        ]);
    }
}
