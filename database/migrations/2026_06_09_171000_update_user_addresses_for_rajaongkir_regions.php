<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->rebuildSqliteTableForUp();
        } else {
            Schema::table('user_addresses', function (Blueprint $table) {
                $table->dropForeign(['province_id']);
                $table->dropForeign(['regency_id']);
                $table->dropForeign(['district_id']);
                $table->dropForeign(['village_id']);
            });

            Schema::table('user_addresses', function (Blueprint $table) {
                $table->string('province_id', 32)->change();
                $table->string('regency_id', 32)->change();
                $table->string('district_id', 32)->change();
                $table->string('village_id', 32)->change();
                $table->string('province_name', 100)->nullable()->after('village_id');
                $table->string('city_name', 100)->nullable()->after('province_name');
                $table->string('district_name', 100)->nullable()->after('city_name');
                $table->string('village_name', 100)->nullable()->after('district_name');
                $table->string('region_source', 30)->default('rajaongkir')->after('village_name');
            });
        }

        $this->backfillRegionSnapshots();
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->rebuildSqliteTableForDown();

            return;
        }

        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropColumn([
                'province_name',
                'city_name',
                'district_name',
                'village_name',
                'region_source',
            ]);
        });

        Schema::table('user_addresses', function (Blueprint $table) {
            $table->char('province_id', 2)->change();
            $table->char('regency_id', 4)->change();
            $table->char('district_id', 7)->change();
            $table->char('village_id', 10)->change();

            $table->foreign('province_id')->references('id')->on('provinces');
            $table->foreign('regency_id')->references('id')->on('regencies');
            $table->foreign('district_id')->references('id')->on('districts');
            $table->foreign('village_id')->references('id')->on('villages');
        });
    }

    private function rebuildSqliteTableForUp(): void
    {
        DB::statement('PRAGMA foreign_keys=OFF');

        try {
            DB::statement(<<<'SQL'
CREATE TABLE user_addresses_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    user_id INTEGER NOT NULL,
    label VARCHAR(50) NOT NULL,
    receiver_name VARCHAR(100) NOT NULL,
    receiver_phone VARCHAR(20) NOT NULL,
    full_address TEXT NOT NULL,
    province_id VARCHAR(32) NOT NULL,
    regency_id VARCHAR(32) NOT NULL,
    district_id VARCHAR(32) NOT NULL,
    village_id VARCHAR(32) NOT NULL,
    province_name VARCHAR(100) NULL,
    city_name VARCHAR(100) NULL,
    district_name VARCHAR(100) NULL,
    village_name VARCHAR(100) NULL,
    region_source VARCHAR(30) NOT NULL DEFAULT 'rajaongkir',
    postal_code VARCHAR(10) NOT NULL,
    is_main TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
SQL);

            DB::statement(<<<'SQL'
INSERT INTO user_addresses_new (
    id,
    user_id,
    label,
    receiver_name,
    receiver_phone,
    full_address,
    province_id,
    regency_id,
    district_id,
    village_id,
    region_source,
    postal_code,
    is_main,
    created_at,
    updated_at
)
SELECT
    id,
    user_id,
    label,
    receiver_name,
    receiver_phone,
    full_address,
    province_id,
    regency_id,
    district_id,
    village_id,
    'indoregion',
    postal_code,
    is_main,
    created_at,
    updated_at
FROM user_addresses
SQL);

            DB::statement('DROP TABLE user_addresses');
            DB::statement('ALTER TABLE user_addresses_new RENAME TO user_addresses');
            DB::statement('CREATE INDEX user_addresses_user_id_index ON user_addresses(user_id)');
        } finally {
            DB::statement('PRAGMA foreign_keys=ON');
        }
    }

    private function rebuildSqliteTableForDown(): void
    {
        DB::statement('PRAGMA foreign_keys=OFF');

        try {
            DB::statement(<<<'SQL'
CREATE TABLE user_addresses_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    user_id INTEGER NOT NULL,
    label VARCHAR(50) NOT NULL,
    receiver_name VARCHAR(100) NOT NULL,
    receiver_phone VARCHAR(20) NOT NULL,
    full_address TEXT NOT NULL,
    province_id CHAR(2) NOT NULL,
    regency_id CHAR(4) NOT NULL,
    district_id CHAR(7) NOT NULL,
    village_id CHAR(10) NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    is_main TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (province_id) REFERENCES provinces(id),
    FOREIGN KEY (regency_id) REFERENCES regencies(id),
    FOREIGN KEY (district_id) REFERENCES districts(id),
    FOREIGN KEY (village_id) REFERENCES villages(id)
)
SQL);

            DB::statement(<<<'SQL'
INSERT INTO user_addresses_new (
    id,
    user_id,
    label,
    receiver_name,
    receiver_phone,
    full_address,
    province_id,
    regency_id,
    district_id,
    village_id,
    postal_code,
    is_main,
    created_at,
    updated_at
)
SELECT
    id,
    user_id,
    label,
    receiver_name,
    receiver_phone,
    full_address,
    province_id,
    regency_id,
    district_id,
    village_id,
    postal_code,
    is_main,
    created_at,
    updated_at
FROM user_addresses
SQL);

            DB::statement('DROP TABLE user_addresses');
            DB::statement('ALTER TABLE user_addresses_new RENAME TO user_addresses');
            DB::statement('CREATE INDEX user_addresses_user_id_index ON user_addresses(user_id)');
        } finally {
            DB::statement('PRAGMA foreign_keys=ON');
        }
    }

    private function backfillRegionSnapshots(): void
    {
        DB::table('user_addresses')
            ->orderBy('id')
            ->get(['id', 'province_id', 'regency_id', 'district_id', 'village_id'])
            ->each(function (object $address): void {
                DB::table('user_addresses')
                    ->where('id', $address->id)
                    ->update([
                        'province_name' => DB::table('provinces')->where('id', $address->province_id)->value('name'),
                        'city_name' => DB::table('regencies')->where('id', $address->regency_id)->value('name'),
                        'district_name' => DB::table('districts')->where('id', $address->district_id)->value('name'),
                        'village_name' => DB::table('villages')->where('id', $address->village_id)->value('name'),
                        'region_source' => 'indoregion',
                    ]);
            });
    }
};
