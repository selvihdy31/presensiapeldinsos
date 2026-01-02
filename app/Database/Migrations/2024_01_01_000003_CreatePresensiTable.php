forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'keterangan' => ['type' => 'ENUM', 'constraint' => ['hadir', 'terlambat', 'ijin']],
            'waktu' => ['type' => 'DATETIME'],
            'latitude' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'longitude' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'lokasi' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('presensi');
    }

    public function down()
    {
        $this->forge->dropTable('presensi');
    }
}