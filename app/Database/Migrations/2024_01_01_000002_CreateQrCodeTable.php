forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tanggal' => ['type' => 'DATE'],
            'waktu_mulai' => ['type' => 'TIME'],
            'waktu_selesai' => ['type' => 'TIME'],
            'token' => ['type' => 'VARCHAR', 'constraint' => 255, 'unique' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['aktif', 'nonaktif'], 'default' => 'aktif'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('qr_code');
    }

    public function down()
    {
        $this->forge->dropTable('qr_code');
    }
}