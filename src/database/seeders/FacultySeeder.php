<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FacultySeeder extends Seeder
{
    /**
     * Maps old dump department_id -> department code.
     * Used to look up the new department ID by code.
     */
    private array $deptIdToCode = [
        1  => 'CCIS',
        4  => 'CTE',
        5  => 'CCJE',
        6  => 'CAS',
        7  => 'CBMA',
        8  => 'CTHM',
        14 => 'EDP',
        15 => 'PMO',
        16 => 'Registrar',
        17 => 'Finance',
        18 => 'OSAS',
        19 => 'RIID',
    ];

    public function run(): void
    {
        $this->command->info('Building department code -> new ID map...');

        // Build a map: code => new_id
        $deptMap = DB::table('departments')->pluck('id', 'code')->toArray();

        // Helper closure: resolve new department ID from old dump dept_id
        $dept = function (int $oldId) use ($deptMap): ?int {
            $code = $this->deptIdToCode[$oldId] ?? null;
            return $code ? ($deptMap[$code] ?? null) : null;
        };

        // -----------------------------------------------------------------------
        // DEAN users  (from users table, role='dean', except admin id=9)
        // faculty_profiles rows exist for deans too (same user_id appears in
        // faculty_profiles for ids 17-21 which map to users 49-53).
        // -----------------------------------------------------------------------

        $this->command->info('Seeding dean users...');

        $deans = [
            // old_user_id => data
            ['old_id' => 10,  'name' => 'Daisa Gupit',          'email' => 'dean.ccis@sample.com',                    'password' => '$2y$12$X9zxuroF.Zzc4S86OPWK8eO9DubcAHxi1FdziYYJGOvWp7bwrs92G', 'old_dept_id' => 1,  'created_at' => '2026-03-07 07:38:59'],
            ['old_id' => 49,  'name' => 'Jun Villarima',         'email' => 'junvillarmia@smccnasipit.edu.ph',          'password' => '$2y$10$p1WiJZabaknTuQpBGMP.Le3md/lXcCUsR1d8Rdk8O4Rl.AdX34nda', 'old_dept_id' => 5,  'created_at' => '2026-03-22 06:21:21'],
            ['old_id' => 50,  'name' => 'Rolly Lianos',          'email' => 'rollyllanos@smccnasipit.edu.ph',           'password' => '$2y$10$MfV2cEQVT8tG1ZDv8M6lRucQznM7VbNa6JoJF4UehpHUbMx1v0k8C', 'old_dept_id' => 6,  'created_at' => '2026-03-22 06:21:39'],
            ['old_id' => 51,  'name' => 'Melisa Maloloy-on',     'email' => 'melisamaloloy-on@smccnasipit.edu.ph',      'password' => '$2y$10$SOTujbbmsavZ9wOmfJvryex9EELAQLwDpdH9kv4jsUGHNH3v4L7si',  'old_dept_id' => 4,  'created_at' => '2026-03-22 06:22:01'],
            ['old_id' => 52,  'name' => 'Russellene Coraza',     'email' => 'russellenecoraza@smccnasipit.edu.ph',      'password' => '$2y$10$m1pwoW9KScqqSU7gijXW4u2GJEkR2Qpr0KeOshQHNP0CqOqpcHy5O', 'old_dept_id' => 7,  'created_at' => '2026-03-22 06:22:20'],
            ['old_id' => 53,  'name' => 'Angeli Paquera',        'email' => 'angelirendon@smccnasipit.edu.ph',          'password' => '$2y$10$jgYAFiUTwbGDppjJa5btke2SOrKMR2O8Z.WHqtOoaXgjoFwq6N0aO', 'old_dept_id' => 8,  'created_at' => '2026-03-22 06:22:41'],
        ];

        // old_user_id -> new_user_id (needed for faculty_profiles of deans)
        $oldUserIdToNew = [];

        foreach ($deans as $row) {
            $newUserId = DB::table('users')->insertGetId([
                'name'                 => $row['name'],
                'email'                => $row['email'],
                'password'             => $row['password'],
                'role'                 => 'dean',
                'is_active'            => true,
                'department_id'        => $dept($row['old_dept_id']),
                'must_change_password' => false,
                'created_at'           => $row['created_at'],
                'updated_at'           => $row['created_at'],
            ]);
            $oldUserIdToNew[$row['old_id']] = $newUserId;
        }

        $this->command->info('Seeded ' . count($deans) . ' deans.');

        // -----------------------------------------------------------------------
        // FACULTY users
        // -----------------------------------------------------------------------

        $this->command->info('Seeding faculty users...');

        $facultyUsers = [
            ['old_id' => 54,  'name' => 'Charmanine Abrea',           'email' => 'charmaineabrea@smccnasipit.edu.ph',              'password' => '$2y$10$m9tTZ8B5PuFZpUFtYlH/kO9icct0VgNY7T6EwnXSaiws9xQ7xlPLe', 'old_dept_id' => 6, 'created_at' => '2026-03-22 06:27:29'],
            ['old_id' => 55,  'name' => 'Cyrlyn Cagande',             'email' => 'cyrlyncagande@smccnasipit.edu.ph',               'password' => '$2y$10$H4ds5OS28B03eEpOHSv06.EZd4ROvmIsnlMtZs0D5prkvN1UeEwoy', 'old_dept_id' => 6, 'created_at' => '2026-03-22 06:28:09'],
            ['old_id' => 56,  'name' => 'Shagne Lim',                 'email' => 'shaynedumadara@smccnasipit.edu.ph',              'password' => '$2y$10$paaEx2fJQBlVWLRq.EzM4OpDkkpyqgEUyPnwcSq/dOOgN2MDIVe1W', 'old_dept_id' => 6, 'created_at' => '2026-03-22 06:28:29'],
            ['old_id' => 57,  'name' => 'Regine Lee Gavero',          'email' => 'regineleegavero@smccnasipit.edu.ph',             'password' => '$2y$10$Vaquk3UITfxN9RK7z3WBWubXHi6UjzKz2W22nEI2pyajlbnO.OTXO', 'old_dept_id' => 6, 'created_at' => '2026-03-22 06:28:57'],
            ['old_id' => 58,  'name' => 'Divina Compendio',           'email' => 'divinacompendio@smccnasipit.edu.ph',             'password' => '$2y$10$86w6ISVYAuZ8ng8FTpiyGOXzEmSEliSfbaYFTmBVVo7ZzIP1LBb8a', 'old_dept_id' => 4, 'created_at' => '2026-03-22 06:29:28'],
            ['old_id' => 59,  'name' => 'Marlon Juhn Timogan',        'email' => 'marlonjhuntimogan@smccnasipit.edu.ph',           'password' => '$2y$10$YrojqcLlz7PcQAv1s7gi7eRUkRMCRIDZlZI1RsxIGw7.CCiuY9l.O', 'old_dept_id' => 1, 'created_at' => '2026-03-22 06:29:55'],
            ['old_id' => 60,  'name' => 'Paul Eric Bacong',           'email' => 'paulericbacong@smccnasipit.edu.ph',              'password' => '$2y$10$2hJO2pz1mbdqKCJuCM.wz.nB7k2TZnn21A8iqboxakn3G4sD9Gtua', 'old_dept_id' => 4, 'created_at' => '2026-03-22 06:32:03'],
            ['old_id' => 61,  'name' => 'Ronah Ong',                  'email' => 'ronahferraren@smccnasipit.edu.ph',               'password' => '$2y$10$Wx5q7dUKOkj0YtiInh57f.X2Ntn86VJ7Oc9pK62Rzfmtvnv63QFgO', 'old_dept_id' => 6, 'created_at' => '2026-03-22 06:32:34'],
            ['old_id' => 62,  'name' => 'Reggy Bartido',              'email' => 'reggybartido@smccnasipit.edu.ph',                'password' => '$2y$10$XksQU4/nw11rhglMs1iIsOV0vSJcYcAVU36cCyXh92wuTlSkd2m6u', 'old_dept_id' => 6, 'created_at' => '2026-03-22 06:34:14'],
            ['old_id' => 63,  'name' => 'Leah Castro',                'email' => 'lea_castro@smccnasipit.edu.ph',                  'password' => '$2y$10$IU4GGVJTQYagUGrn8b55DeDBw9jOmKr7jgQf6bwNE/fOapPT4meNq', 'old_dept_id' => 6, 'created_at' => '2026-03-22 06:34:41'],
            ['old_id' => 64,  'name' => 'Jessa Claire Cabusao',       'email' => 'clairecabusao@smccnasipit.edu.ph',               'password' => '$2y$10$c5x1ZtJDGEEC6T5lO4nfKOnKyyk3ByQj7XA3ky4mbfyGgmMgIUz52', 'old_dept_id' => 6, 'created_at' => '2026-03-22 06:35:18'],
            ['old_id' => 65,  'name' => 'Maricel Pabatang',           'email' => 'maricelpabatang@smccnasipit.edu.ph',             'password' => '$2y$10$Lk0IYvrJ37eAb640hnHsbO.vVQf0LwnIA6FgJFKXCkPaHPdDairG6', 'old_dept_id' => 8, 'created_at' => '2026-03-22 06:35:47'],
            ['old_id' => 66,  'name' => 'Lawrence Soria',             'email' => 'lawrencesoria@smccnasipit.edu.ph',               'password' => '$2y$10$4rOm1Joyrcq/d/XiWzxpV.7MbWND/2dvdcISHReT4wV8qw3q73UqC', 'old_dept_id' => 6, 'created_at' => '2026-03-22 06:36:06'],
            ['old_id' => 67,  'name' => 'Allen Salar',                'email' => 'allenvallecera@smccnasipit.edu.ph',              'password' => '$2y$10$7SDnXei39ArxdePqMm2ir.k1cPaZ8DDE5d6uUOQTfSfeX1k6NU17S', 'old_dept_id' => 6, 'created_at' => '2026-03-22 06:36:35'],
            ['old_id' => 68,  'name' => 'Hannah Jane Erong',          'email' => 'hannaherong@smccnasipit.edu.ph',                 'password' => '$2y$10$GaGABdAYgjaJHjS6jBe5t.KuMJ2OsQ9c9w3telqDieOy4rQxXTL5S', 'old_dept_id' => 4, 'created_at' => '2026-03-22 06:36:58'],
            ['old_id' => 69,  'name' => 'Norquiza Dianal',            'email' => 'norquizadianal@smccnasipit.edu.ph',              'password' => '$2y$10$y95ALv9oBnj78qomziJ3uebS8PCRj4XtiQ7d2EtqlmB3Q9f4YEEjS', 'old_dept_id' => 6, 'created_at' => '2026-03-22 06:37:35'],
            ['old_id' => 70,  'name' => 'Norhoney Panarigan',         'email' => 'norhoneypanarigan@smccnasipit.edu.ph',           'password' => '$2y$10$OpI/Z35LUf0j9Up0oc/qcOCSgIgbJTW3sVd0TqFGiSkbV.gRFY8ka', 'old_dept_id' => 6, 'created_at' => '2026-03-22 06:37:57'],
            ['old_id' => 71,  'name' => 'Perlyn Jay Paredes',         'email' => 'perlynparedes@smccnasipit.edu.ph',               'password' => '$2y$10$m/MJQxgKHxbhjMPEhfr7/.Nun6HeDiLA431BUEi58L/lBoWOI5E6.',  'old_dept_id' => 4, 'created_at' => '2026-03-22 06:38:29'],
            ['old_id' => 72,  'name' => 'Erly Basalo',                'email' => 'erlybasalo@smccnasipit.edu.ph',                  'password' => '$2y$10$6ELW6WMs2VzkhdxrAnXWbu.AuParpr2Ye6/hcz8Q4AT1qFC/VxAWK',  'old_dept_id' => 6, 'created_at' => '2026-03-22 06:39:05'],
            ['old_id' => 73,  'name' => 'Kenneth Edward Paceno',      'email' => 'kenpaceno@smccnasipit.edu.ph',                   'password' => '$2y$10$Hmid7iS0Bc4ZFGACHHmqo.uxbb1y8uEI57yGX8WWcPSJ7OmhoDOSa', 'old_dept_id' => 6, 'created_at' => '2026-03-22 06:39:29'],
            ['old_id' => 74,  'name' => 'Joanne Colegado',            'email' => 'colegadojoanne@smccnasipit.edu.ph',              'password' => '$2y$10$Ph8bfutlLT3amNyl1aVPZOWDckY6ptKZ99xDyOCA.Qzx/uYteUZ/C', 'old_dept_id' => 4, 'created_at' => '2026-03-22 06:39:47'],
            ['old_id' => 75,  'name' => 'Mart PJ Segundino',          'email' => 'martsegundino@smccnasipit.edu.ph',               'password' => '$2y$10$9UKz7jen6XCSir2NUzIwVOY2pn3SvtU6WCYFkA0L.Lsd8EOw8kM6W', 'old_dept_id' => 4, 'created_at' => '2026-03-22 06:40:17'],
            ['old_id' => 76,  'name' => 'Desiree May Abian',          'email' => 'desireeabian@smccnasipit.edu.ph',                'password' => '$2y$10$YBAtOecp4VO9JTp0P05epedlgRHCqDSTNTsF7ikDM4Wm/RSLIbpx6', 'old_dept_id' => 4, 'created_at' => '2026-03-22 06:40:36'],
            ['old_id' => 77,  'name' => 'Rachel Saladaga',            'email' => 'rachelsaladaga@smccnasipit.edu.ph',              'password' => '$2y$10$5NnvNxU0GDeB2INTDSs11O7lCiRB6JdP6WqMyOiQDqrWIl2dwwG0m', 'old_dept_id' => 6, 'created_at' => '2026-03-22 06:40:55'],
            ['old_id' => 78,  'name' => 'Gary Cris Pelegrino',        'email' => 'garycrispelegrino@smccnasipit.edu.ph',           'password' => '$2y$10$ddbF9YdTiJ3gCHnFswjxiepCoYFjb/lpRDay.dRsKs4QpkEJJxfei', 'old_dept_id' => 7, 'created_at' => '2026-03-22 06:41:22'],
            ['old_id' => 79,  'name' => 'Noel Bitangcor',             'email' => 'noelbitangcor@smccnasipit.edu.ph',               'password' => '$2y$10$q43D6x16o/EmzQOrSTcSJ.I5WQNV9OzoNx0CIsSJE/DaCALbiYgb.',  'old_dept_id' => 7, 'created_at' => '2026-03-22 06:41:38'],
            ['old_id' => 80,  'name' => 'Phoebe Keit Gulleban',       'email' => 'phoebesaagundo@smccnasipit.edu.ph',              'password' => '$2y$10$534Bw998YMO8PLfJZP4orumDsOJHBg3Wpra/R8QDRG9hfS3uvS7Nm', 'old_dept_id' => 7, 'created_at' => '2026-03-22 06:42:03'],
            ['old_id' => 81,  'name' => 'Kaye Abian',                 'email' => 'kayeabian@smccnasipit.edu.ph',                   'password' => '$2y$10$s7YlMKsV1y.gWbgld05d1efsOhmem0GeoOzAPsvWhM8lgekMU6nea',  'old_dept_id' => 7, 'created_at' => '2026-03-22 06:42:23'],
            ['old_id' => 82,  'name' => 'Ronie Ho',                   'email' => 'ronieho@smccnasipit.edu.ph',                     'password' => '$2y$10$Qa8OxlaJxxKyFd7sQYYqkeSLCr2SPD2FL7NBZLcNNU/2TeZ.1ejkO', 'old_dept_id' => 7, 'created_at' => '2026-03-22 06:42:43'],
            ['old_id' => 83,  'name' => 'Renita Lourdes Lagapa',      'email' => 'renitalagapa@smccnasipit.edu.ph',                'password' => '$2y$10$gA.bnlIFSHLXW4qeVfLpZOkAaPRul4D2ov7vFO4lfhF6dTcIe0S.C', 'old_dept_id' => 7, 'created_at' => '2026-03-22 06:43:05'],
            ['old_id' => 84,  'name' => 'Claire Obedencio',           'email' => 'claireoblimar@smccnasipit.edu.ph',               'password' => '$2y$10$gOFKDK8/5n4LhW/zWCb30uL72atqUsrBUO35LtmMvFP9t9dU3M64y', 'old_dept_id' => 7, 'created_at' => '2026-03-22 06:43:31'],
            ['old_id' => 85,  'name' => 'Jessie Mahinay',             'email' => 'jessiemahinay@smccnasipit.edu.ph',               'password' => '$2y$10$Z4/C3butdwEij4BU9kk18e0EDUFBwY1R3YdNkNJxYrX8bt1ZEaaR2', 'old_dept_id' => 1, 'created_at' => '2026-03-22 06:43:51'],
            ['old_id' => 86,  'name' => 'Rea Mie Omas-as',            'email' => 'reaomas-as@smccnasipit.edu.ph',                  'password' => '$2y$10$BL4I42z5n5jsY9jPwLJXV.rrxHRnIeWiRZTyeG30UgGFEKRJ3u9ri', 'old_dept_id' => 1, 'created_at' => '2026-03-22 06:44:22'],
            ['old_id' => 87,  'name' => 'Contisza Abadiez',           'email' => 'contiszaabadiez@smccnasipit.edu.ph',             'password' => '$2y$10$OY3QIl0bBv62/8w9kXcZ8uZdGJjdEkWQxDEiakARPPqlny79NN39u', 'old_dept_id' => 1, 'created_at' => '2026-03-22 06:45:01'],
            ['old_id' => 88,  'name' => 'Lealil Palacio',             'email' => 'lealilpalacio@smccnasipit.edu.ph',               'password' => '$2y$10$8lqM7MHCO2dfe5exxkH2fu7tn4WRBry8IqDKMg7itPewZtRH.qk2O', 'old_dept_id' => 1, 'created_at' => '2026-03-22 06:45:21'],
            ['old_id' => 89,  'name' => 'Ronnel Falo',                'email' => 'ronnelfalo@smccnasipit.edu.ph',                  'password' => '$2y$10$ngt0Z0//iUc9hpnBmNG1Jea3mS6P0ur27WCWO.oXECgEmIlrYMJsy', 'old_dept_id' => 1, 'created_at' => '2026-03-22 06:50:27'],
            ['old_id' => 90,  'name' => 'Reginald Ryan Gosela',       'email' => 'reginaldryangosela@smccnasipit.edu.ph',          'password' => '$2y$10$4qH1B7qTZDmf5NCpoEDkReFgP2KH/9gy7bF/dHhOX6ot0ga7eBe2y', 'old_dept_id' => 1, 'created_at' => '2026-03-22 06:50:50'],
            ['old_id' => 91,  'name' => 'issrel Acabo',               'email' => 'jissrelacabo@smccnasipit.edu.ph',                'password' => '$2y$10$eXDdW8PR/8c3iA.828UBO.deuZQEBKtnjLvozzAGgVP1nKqfmFd/2', 'old_dept_id' => 5, 'created_at' => '2026-03-22 06:51:07'],
            ['old_id' => 92,  'name' => 'Reyniemor Anciano',          'email' => 'reyniemor-anciano@smccnasipit.edu.ph',           'password' => '$2y$10$9yTqwNbt9OXZGqGN9l1bp.QihGgg5u8y3o9qKZ5/cFa.rmq1MEoc2', 'old_dept_id' => 5, 'created_at' => '2026-03-22 06:51:23'],
            ['old_id' => 93,  'name' => 'Rielvent Telen',             'email' => 'rielventelen@smccnasipit.edu.ph',                'password' => '$2y$10$nVvgffqtY6guT2.CktDT/uw1Jis88b7.h.VTRQ0GayG6SeLtOWbmO', 'old_dept_id' => 5, 'created_at' => '2026-03-22 06:51:41'],
            ['old_id' => 94,  'name' => 'Kelvin Cepe',                'email' => 'kelvincepe@smccnasipit.edu.ph',                  'password' => '$2y$10$wzOJx3m5o2QcvDDU89MEsOJN5uff33.NOqH1rrV9Fxftui53nwtC.',  'old_dept_id' => 5, 'created_at' => '2026-03-22 06:52:00'],
            ['old_id' => 95,  'name' => 'Lenny Rosvette Cacayan',     'email' => 'lennycacayan@smccnasipit.edu.ph',                'password' => '$2y$10$07gmkPFm0vL4VyGsOFWKSu8Ydra.4w1StVEtJvkKEBr4Wjv/ZGGxC', 'old_dept_id' => 5, 'created_at' => '2026-03-22 06:52:16'],
            ['old_id' => 96,  'name' => 'Jill Mesa',                  'email' => 'jillmesa@smccnasipit.edu.ph',                    'password' => '$2y$10$91lWEKwNORT63O5pblIOOOP98EsRA5sfb0NsOVUCnU6Yn2jon2dH6',  'old_dept_id' => 5, 'created_at' => '2026-03-22 06:52:36'],
            ['old_id' => 97,  'name' => 'Angelikka Faustaen Kane Go', 'email' => 'angelikkafaustaenago@smccnasipit.edu.ph',        'password' => '$2y$10$OJS2gwpPWKH42U/6kZk/JOmpBMEw8QX3YBSunpgv/fFWI/baj56TC', 'old_dept_id' => 5, 'created_at' => '2026-03-22 06:52:56'],
            ['old_id' => 98,  'name' => 'Rhaneth Olvis',              'email' => 'rhanetholvis@smccnasipit.edu.ph',                'password' => '$2y$10$6GSj021VM1km.OYfLZeMZu4Uo4bn40MVGMI8spl6LRnk3yKU/oOk2', 'old_dept_id' => 5, 'created_at' => '2026-03-22 06:53:17'],
            ['old_id' => 99,  'name' => 'Sheena Mae Gallenero',       'email' => 'sheenagallenero@smccnasipit.edu.ph',             'password' => '$2y$10$d49UIP9a8bfiHi/CyGvNOOrBYi6OG0M79/OS8L6UinrwLFPSOd3mK', 'old_dept_id' => 5, 'created_at' => '2026-03-22 06:53:38'],
            ['old_id' => 100, 'name' => 'Jerry Galdiano',             'email' => 'jerrygaldiano09@smccnasipit.edu.ph',             'password' => '$2y$10$hwi6NIXTISF7uzMdGeSdGeR9ppL7/9lXtF4NWc2KYqayxrtLwvXdW', 'old_dept_id' => 4, 'created_at' => '2026-03-22 06:54:01'],
            ['old_id' => 101, 'name' => 'Bill Guergio',               'email' => 'billguergio@smccnasipit.edu.ph',                 'password' => '$2y$10$KMbHC.caojs/p.bULO08jO97xpt4iUqEQZw8fY/4qHC9.JJpgYy9e', 'old_dept_id' => 4, 'created_at' => '2026-03-22 06:54:21'],
            ['old_id' => 102, 'name' => 'Aimellin Socorin',           'email' => 'aimellinamarilla-socorin@smccnasipit.edu.ph',    'password' => '$2y$10$ijsw8AfRGyoHKFq1yLqCQOIEEDjtJagI3H7iJygpsBGQ1B/jp2Bv6', 'old_dept_id' => 4, 'created_at' => '2026-03-22 06:54:45'],
            ['old_id' => 103, 'name' => 'Cezar Polistico',            'email' => 'cezarpolistico@smccnasipit.edu.ph',              'password' => '$2y$10$R6nJNu8OA4.sOP3vA1/ODe6FXUcTO.WwysQYWdUo77mNthpRrVuhu', 'old_dept_id' => 4, 'created_at' => '2026-03-22 06:55:56'],
            ['old_id' => 104, 'name' => 'Theresa Mae Bedayo',         'email' => 'theresamaebedayo@smccnasipit.edu.ph',            'password' => '$2y$10$BIvvZ.djYdyYwNG3k9LIP.iaaXZ1aPYyJfyD.A.MKeqPmkWdRDzbW', 'old_dept_id' => 4, 'created_at' => '2026-03-22 06:56:21'],
            ['old_id' => 105, 'name' => 'Ryan Lagbas',                'email' => 'ryanlagbas@smccnasipit.edu.ph',                  'password' => '$2y$10$wtps63ChIF87O./T96J/yO1HVOrjbfHYLO79BgonsqMf8RD5MW0Pu', 'old_dept_id' => 4, 'created_at' => '2026-03-22 06:56:41'],
            ['old_id' => 106, 'name' => 'Ronald Ompoc',               'email' => 'ronaldompoc@smccnasipit.edu.ph',                 'password' => '$2y$10$tsBC.Dtf.3Kj0y93J3HqDuY7JuedXZn4qq2p.4ldt.8tz1DTw.1ly', 'old_dept_id' => 4, 'created_at' => '2026-03-22 06:56:56'],
            ['old_id' => 107, 'name' => 'Edmund Mendoza',             'email' => 'edmundmendoza@smccnasipit.edu.ph',               'password' => '$2y$10$xx7ora0f0RHuaYyMzeXRWuLFc5nUAcHCIgYs25P3NZxfnq5tSNwf6', 'old_dept_id' => 4, 'created_at' => '2026-03-22 06:57:17'],
            ['old_id' => 108, 'name' => 'Jennifer Destacamento',      'email' => 'jenniferdestacamento@smccnasipit.edu.ph',        'password' => '$2y$10$Oya4cWLcbQKhkwvRb8vhDe8EqXUTkohZIk3isnyvz6/hZYpi1FS6G', 'old_dept_id' => 4, 'created_at' => '2026-03-22 06:57:52'],
            ['old_id' => 109, 'name' => 'Ma. Eva Liza Del Mar',       'email' => 'evalizadelmar@smccnasipit.edu.ph',               'password' => '$2y$10$wPZQ.0si.wf3BX0SmJD6W.NnBrLP.NuMAinXD4e/RfcFZF2oEXe5i', 'old_dept_id' => 8, 'created_at' => '2026-03-22 06:58:13'],
            ['old_id' => 110, 'name' => 'Dominique Abarquez',         'email' => 'dominiqueabarquez@smccnasipit.edu.ph',           'password' => '$2y$10$IUfXFL0nUqJdK2Yb9zZDNu6g/RraK1EXDDG1BaQCcaQ4s0wpr8zHu', 'old_dept_id' => 8, 'created_at' => '2026-03-22 06:58:35'],
            ['old_id' => 111, 'name' => 'Maria Leanoro Parillo',      'email' => 'marisolparillo@smccnasipit.edu.ph',              'password' => '$2y$10$dJvosbmqG2IEaTSPGIwd0.nwVDd35GWEnsXTfbWbpGs09w7yP9k4G', 'old_dept_id' => 8, 'created_at' => '2026-03-22 06:58:59'],
            ['old_id' => 112, 'name' => 'Avelino Piencenaves',        'email' => 'avelinopiencenaves@smccnasipit.edu.ph',          'password' => '$2y$10$c44F12Ikay1lvOHlqUjtmucxLF1u3tXbyfAW.1nddyV68us37IUVa', 'old_dept_id' => 6, 'created_at' => '2026-03-22 06:59:25'],
            ['old_id' => 113, 'name' => 'Marwin Biejo',               'email' => 'marwinbiejo@smccnasipit.edu.ph',                 'password' => '$2y$10$JxQTxuTqQv2330U0RmsYYuLot2oGSzEC5mjpWwHxZYDLDobNbETxO', 'old_dept_id' => 6, 'created_at' => '2026-03-22 12:35:35'],
            ['old_id' => 114, 'name' => 'Jusalyn Domingo',            'email' => 'jusalyndomingo@smccnasipit.edu.ph',              'password' => '$2y$10$y8tVpMevLC/Gc3TCi3hc4OjuiIWdU6h5/HNoI6vmbnF/3ODTf3Bbq', 'old_dept_id' => 6, 'created_at' => '2026-03-22 12:38:00'],
        ];

        foreach ($facultyUsers as $row) {
            $newUserId = DB::table('users')->insertGetId([
                'name'                 => $row['name'],
                'email'                => $row['email'],
                'password'             => $row['password'],
                'role'                 => 'faculty',
                'is_active'            => true,
                'department_id'        => $dept($row['old_dept_id']),
                'must_change_password' => false,
                'created_at'           => $row['created_at'],
                'updated_at'           => $row['created_at'],
            ]);
            $oldUserIdToNew[$row['old_id']] = $newUserId;
        }

        $this->command->info('Seeded ' . count($facultyUsers) . ' faculty users.');

        // -----------------------------------------------------------------------
        // FACULTY PROFILES
        // Dump: (profile_id, user_id, department_id, created_at)
        // We store old_profile_id -> new_profile_id in a cache file so
        // SubjectAssignmentSeeder can use it.
        // -----------------------------------------------------------------------

        $this->command->info('Seeding faculty_profiles...');

        // Maps old faculty_profile.id -> [old_user_id, old_dept_id]
        $profileRows = [
            ['old_profile_id' => 17, 'old_user_id' => 49,  'old_dept_id' => 5,  'created_at' => '2026-03-22 06:21:21'],
            ['old_profile_id' => 18, 'old_user_id' => 50,  'old_dept_id' => 6,  'created_at' => '2026-03-22 06:21:39'],
            ['old_profile_id' => 19, 'old_user_id' => 51,  'old_dept_id' => 4,  'created_at' => '2026-03-22 06:22:01'],
            ['old_profile_id' => 20, 'old_user_id' => 52,  'old_dept_id' => 7,  'created_at' => '2026-03-22 06:22:20'],
            ['old_profile_id' => 21, 'old_user_id' => 53,  'old_dept_id' => 8,  'created_at' => '2026-03-22 06:22:41'],
            ['old_profile_id' => 22, 'old_user_id' => 54,  'old_dept_id' => 6,  'created_at' => '2026-03-22 06:27:29'],
            ['old_profile_id' => 23, 'old_user_id' => 55,  'old_dept_id' => 6,  'created_at' => '2026-03-22 06:28:09'],
            ['old_profile_id' => 24, 'old_user_id' => 56,  'old_dept_id' => 6,  'created_at' => '2026-03-22 06:28:29'],
            ['old_profile_id' => 25, 'old_user_id' => 57,  'old_dept_id' => 6,  'created_at' => '2026-03-22 06:28:57'],
            ['old_profile_id' => 26, 'old_user_id' => 58,  'old_dept_id' => 4,  'created_at' => '2026-03-22 06:29:28'],
            ['old_profile_id' => 27, 'old_user_id' => 59,  'old_dept_id' => 1,  'created_at' => '2026-03-22 06:29:55'],
            ['old_profile_id' => 28, 'old_user_id' => 60,  'old_dept_id' => 4,  'created_at' => '2026-03-22 06:32:03'],
            ['old_profile_id' => 29, 'old_user_id' => 61,  'old_dept_id' => 6,  'created_at' => '2026-03-22 06:32:34'],
            ['old_profile_id' => 30, 'old_user_id' => 62,  'old_dept_id' => 6,  'created_at' => '2026-03-22 06:34:14'],
            ['old_profile_id' => 31, 'old_user_id' => 63,  'old_dept_id' => 6,  'created_at' => '2026-03-22 06:34:41'],
            ['old_profile_id' => 32, 'old_user_id' => 64,  'old_dept_id' => 6,  'created_at' => '2026-03-22 06:35:18'],
            ['old_profile_id' => 33, 'old_user_id' => 65,  'old_dept_id' => 8,  'created_at' => '2026-03-22 06:35:47'],
            ['old_profile_id' => 34, 'old_user_id' => 66,  'old_dept_id' => 6,  'created_at' => '2026-03-22 06:36:06'],
            ['old_profile_id' => 35, 'old_user_id' => 67,  'old_dept_id' => 6,  'created_at' => '2026-03-22 06:36:35'],
            ['old_profile_id' => 36, 'old_user_id' => 68,  'old_dept_id' => 4,  'created_at' => '2026-03-22 06:36:58'],
            ['old_profile_id' => 37, 'old_user_id' => 69,  'old_dept_id' => 6,  'created_at' => '2026-03-22 06:37:35'],
            ['old_profile_id' => 38, 'old_user_id' => 70,  'old_dept_id' => 6,  'created_at' => '2026-03-22 06:37:57'],
            ['old_profile_id' => 39, 'old_user_id' => 71,  'old_dept_id' => 4,  'created_at' => '2026-03-22 06:38:29'],
            ['old_profile_id' => 40, 'old_user_id' => 72,  'old_dept_id' => 6,  'created_at' => '2026-03-22 06:39:05'],
            ['old_profile_id' => 41, 'old_user_id' => 73,  'old_dept_id' => 6,  'created_at' => '2026-03-22 06:39:29'],
            ['old_profile_id' => 42, 'old_user_id' => 74,  'old_dept_id' => 4,  'created_at' => '2026-03-22 06:39:47'],
            ['old_profile_id' => 43, 'old_user_id' => 75,  'old_dept_id' => 4,  'created_at' => '2026-03-22 06:40:17'],
            ['old_profile_id' => 44, 'old_user_id' => 76,  'old_dept_id' => 4,  'created_at' => '2026-03-22 06:40:36'],
            ['old_profile_id' => 45, 'old_user_id' => 77,  'old_dept_id' => 6,  'created_at' => '2026-03-22 06:40:55'],
            ['old_profile_id' => 46, 'old_user_id' => 78,  'old_dept_id' => 7,  'created_at' => '2026-03-22 06:41:22'],
            ['old_profile_id' => 47, 'old_user_id' => 79,  'old_dept_id' => 7,  'created_at' => '2026-03-22 06:41:38'],
            ['old_profile_id' => 48, 'old_user_id' => 80,  'old_dept_id' => 7,  'created_at' => '2026-03-22 06:42:03'],
            ['old_profile_id' => 49, 'old_user_id' => 81,  'old_dept_id' => 7,  'created_at' => '2026-03-22 06:42:23'],
            ['old_profile_id' => 50, 'old_user_id' => 82,  'old_dept_id' => 7,  'created_at' => '2026-03-22 06:42:43'],
            ['old_profile_id' => 51, 'old_user_id' => 83,  'old_dept_id' => 7,  'created_at' => '2026-03-22 06:43:05'],
            ['old_profile_id' => 52, 'old_user_id' => 84,  'old_dept_id' => 7,  'created_at' => '2026-03-22 06:43:31'],
            ['old_profile_id' => 53, 'old_user_id' => 85,  'old_dept_id' => 1,  'created_at' => '2026-03-22 06:43:51'],
            ['old_profile_id' => 54, 'old_user_id' => 86,  'old_dept_id' => 1,  'created_at' => '2026-03-22 06:44:22'],
            ['old_profile_id' => 55, 'old_user_id' => 87,  'old_dept_id' => 1,  'created_at' => '2026-03-22 06:45:01'],
            ['old_profile_id' => 56, 'old_user_id' => 88,  'old_dept_id' => 1,  'created_at' => '2026-03-22 06:45:21'],
            ['old_profile_id' => 57, 'old_user_id' => 89,  'old_dept_id' => 1,  'created_at' => '2026-03-22 06:50:27'],
            ['old_profile_id' => 58, 'old_user_id' => 90,  'old_dept_id' => 1,  'created_at' => '2026-03-22 06:50:50'],
            ['old_profile_id' => 59, 'old_user_id' => 91,  'old_dept_id' => 5,  'created_at' => '2026-03-22 06:51:07'],
            ['old_profile_id' => 60, 'old_user_id' => 92,  'old_dept_id' => 5,  'created_at' => '2026-03-22 06:51:23'],
            ['old_profile_id' => 61, 'old_user_id' => 93,  'old_dept_id' => 5,  'created_at' => '2026-03-22 06:51:41'],
            ['old_profile_id' => 62, 'old_user_id' => 94,  'old_dept_id' => 5,  'created_at' => '2026-03-22 06:52:00'],
            ['old_profile_id' => 63, 'old_user_id' => 95,  'old_dept_id' => 5,  'created_at' => '2026-03-22 06:52:16'],
            ['old_profile_id' => 64, 'old_user_id' => 96,  'old_dept_id' => 5,  'created_at' => '2026-03-22 06:52:36'],
            ['old_profile_id' => 65, 'old_user_id' => 97,  'old_dept_id' => 5,  'created_at' => '2026-03-22 06:52:56'],
            ['old_profile_id' => 66, 'old_user_id' => 98,  'old_dept_id' => 5,  'created_at' => '2026-03-22 06:53:17'],
            ['old_profile_id' => 67, 'old_user_id' => 99,  'old_dept_id' => 5,  'created_at' => '2026-03-22 06:53:38'],
            ['old_profile_id' => 68, 'old_user_id' => 100, 'old_dept_id' => 4,  'created_at' => '2026-03-22 06:54:01'],
            ['old_profile_id' => 69, 'old_user_id' => 101, 'old_dept_id' => 4,  'created_at' => '2026-03-22 06:54:21'],
            ['old_profile_id' => 70, 'old_user_id' => 102, 'old_dept_id' => 4,  'created_at' => '2026-03-22 06:54:45'],
            ['old_profile_id' => 71, 'old_user_id' => 103, 'old_dept_id' => 4,  'created_at' => '2026-03-22 06:55:56'],
            ['old_profile_id' => 72, 'old_user_id' => 104, 'old_dept_id' => 4,  'created_at' => '2026-03-22 06:56:21'],
            ['old_profile_id' => 73, 'old_user_id' => 105, 'old_dept_id' => 4,  'created_at' => '2026-03-22 06:56:41'],
            ['old_profile_id' => 74, 'old_user_id' => 106, 'old_dept_id' => 4,  'created_at' => '2026-03-22 06:56:56'],
            ['old_profile_id' => 75, 'old_user_id' => 107, 'old_dept_id' => 4,  'created_at' => '2026-03-22 06:57:17'],
            ['old_profile_id' => 76, 'old_user_id' => 108, 'old_dept_id' => 4,  'created_at' => '2026-03-22 06:57:52'],
            ['old_profile_id' => 77, 'old_user_id' => 109, 'old_dept_id' => 8,  'created_at' => '2026-03-22 06:58:13'],
            ['old_profile_id' => 78, 'old_user_id' => 110, 'old_dept_id' => 8,  'created_at' => '2026-03-22 06:58:35'],
            ['old_profile_id' => 79, 'old_user_id' => 111, 'old_dept_id' => 8,  'created_at' => '2026-03-22 06:58:59'],
            ['old_profile_id' => 80, 'old_user_id' => 112, 'old_dept_id' => 6,  'created_at' => '2026-03-22 06:59:25'],
            ['old_profile_id' => 81, 'old_user_id' => 113, 'old_dept_id' => 6,  'created_at' => '2026-03-22 12:35:35'],
            ['old_profile_id' => 82, 'old_user_id' => 114, 'old_dept_id' => 6,  'created_at' => '2026-03-22 12:38:00'],
        ];

        // old_profile_id -> new_profile_id mapping (persisted to storage for SubjectAssignmentSeeder)
        $oldProfileToNew = [];

        foreach ($profileRows as $row) {
            $newUserId = $oldUserIdToNew[$row['old_user_id']] ?? null;
            if ($newUserId === null) {
                $this->command->warn("No new user_id mapping for old_user_id={$row['old_user_id']}, skipping profile.");
                continue;
            }

            $newProfileId = DB::table('faculty_profiles')->insertGetId([
                'user_id'              => $newUserId,
                'department_id'        => $dept($row['old_dept_id']),
                'department_position'  => 'faculty',
                'created_at'           => $row['created_at'],
            ]);

            $oldProfileToNew[$row['old_profile_id']] = $newProfileId;
        }

        // Persist the old->new profile ID map so SubjectAssignmentSeeder can read it
        $mapPath = storage_path('app/faculty_profile_id_map.json');
        file_put_contents($mapPath, json_encode($oldProfileToNew));

        $this->command->info('Seeded ' . count($oldProfileToNew) . ' faculty_profiles. Map saved to ' . $mapPath);
    }
}
