<?php

namespace Database\Seeders;

use App\Models\Criterion;
use Illuminate\Database\Seeder;

class CriteriaSeeder extends Seeder
{
    public function run(): void
    {
        $criteria = [
            // ----------------------------------------------------------------
            // Student evaluator - Teaching personnel
            // ----------------------------------------------------------------
            ['id' => 1,  'name' => 'PROFESSIONAL ATTITUDE AND APPEARANCE',           'personnel_type' => 'teaching',     'evaluator_group' => 'student'],
            ['id' => 2,  'name' => 'KNOWLEDGE OF SUBJECT MATTER',                   'personnel_type' => 'teaching',     'evaluator_group' => 'student'],
            ['id' => 3,  'name' => 'TEACHING SKILLS',                               'personnel_type' => 'teaching',     'evaluator_group' => 'student'],
            ['id' => 4,  'name' => 'CLASSROOM MANAGEMENT',                          'personnel_type' => 'teaching',     'evaluator_group' => 'student'],
            ['id' => 5,  'name' => 'ASSESSMENT OF LEARNING',                        'personnel_type' => 'teaching',     'evaluator_group' => 'student'],
            ['id' => 6,  'name' => 'GENERAL OBSERVATION',                           'personnel_type' => 'teaching',     'evaluator_group' => 'student'],

            // ----------------------------------------------------------------
            // Student evaluator - Non-teaching personnel
            // ----------------------------------------------------------------
            ['id' => 7,  'name' => 'QUALITY OF WORK',                               'personnel_type' => 'non-teaching', 'evaluator_group' => 'student'],
            ['id' => 8,  'name' => 'QUANTITY OF WORK',                              'personnel_type' => 'non-teaching', 'evaluator_group' => 'student'],
            ['id' => 9,  'name' => 'KNOWLEDGE OF WORK',                             'personnel_type' => 'non-teaching', 'evaluator_group' => 'student'],
            ['id' => 10, 'name' => 'REALIABILITY',                                  'personnel_type' => 'non-teaching', 'evaluator_group' => 'student'],
            ['id' => 11, 'name' => 'COOPERATION',                                   'personnel_type' => 'non-teaching', 'evaluator_group' => 'student'],
            ['id' => 12, 'name' => 'INITIATIVE',                                    'personnel_type' => 'non-teaching', 'evaluator_group' => 'student'],
            ['id' => 13, 'name' => 'INITIATIVE',                                    'personnel_type' => 'non-teaching', 'evaluator_group' => 'student'],

            // ----------------------------------------------------------------
            // Dean/Head evaluator - Teaching personnel
            // ----------------------------------------------------------------
            ['id' => 14, 'name' => 'PROFESSIONAL ATTITUDE AND APPEARANCE',           'personnel_type' => 'teaching',     'evaluator_group' => 'dean'],
            ['id' => 15, 'name' => 'KNOWLEDGE OF SUBJECT MATTER',                   'personnel_type' => 'teaching',     'evaluator_group' => 'dean'],
            ['id' => 16, 'name' => 'TEACHING SKILLS',                               'personnel_type' => 'teaching',     'evaluator_group' => 'dean'],
            ['id' => 17, 'name' => 'CLASSROOM MANAGEMENT',                          'personnel_type' => 'teaching',     'evaluator_group' => 'dean'],
            ['id' => 18, 'name' => 'ASSESSMENT OF LEARNING',                        'personnel_type' => 'teaching',     'evaluator_group' => 'dean'],
            ['id' => 19, 'name' => 'GENERAL OBSERVATION',                           'personnel_type' => 'teaching',     'evaluator_group' => 'dean'],

            // ----------------------------------------------------------------
            // Dean/Head evaluator - Non-teaching personnel
            // ----------------------------------------------------------------
            ['id' => 20, 'name' => 'QUALITY OF WORK',                               'personnel_type' => 'non-teaching', 'evaluator_group' => 'dean'],
            ['id' => 21, 'name' => 'QUANTITY OF WORK',                              'personnel_type' => 'non-teaching', 'evaluator_group' => 'dean'],
            ['id' => 22, 'name' => 'KNOWLEDGE OF WORK',                             'personnel_type' => 'non-teaching', 'evaluator_group' => 'dean'],
            ['id' => 23, 'name' => 'REALIABILITY',                                  'personnel_type' => 'non-teaching', 'evaluator_group' => 'dean'],
            ['id' => 24, 'name' => 'COOPERATION',                                   'personnel_type' => 'non-teaching', 'evaluator_group' => 'dean'],
            ['id' => 25, 'name' => 'INITIATIVE',                                    'personnel_type' => 'non-teaching', 'evaluator_group' => 'dean'],
            ['id' => 26, 'name' => 'INITIATIVE',                                    'personnel_type' => 'non-teaching', 'evaluator_group' => 'dean'],

            // ----------------------------------------------------------------
            // Self evaluator - Teaching personnel
            // ----------------------------------------------------------------
            ['id' => 27, 'name' => 'PROFESSIONAL ATTITUDE AND APPEARANCE',           'personnel_type' => 'teaching',     'evaluator_group' => 'self'],
            ['id' => 28, 'name' => 'KNOWLEDGE OF SUBJECT MATTER',                   'personnel_type' => 'teaching',     'evaluator_group' => 'self'],
            ['id' => 29, 'name' => 'TEACHING SKILLS',                               'personnel_type' => 'teaching',     'evaluator_group' => 'self'],
            ['id' => 30, 'name' => 'CLASSROOM MANAGEMENT',                          'personnel_type' => 'teaching',     'evaluator_group' => 'self'],
            ['id' => 31, 'name' => 'ASSESSMENT OF LEARNING',                        'personnel_type' => 'teaching',     'evaluator_group' => 'self'],
            ['id' => 32, 'name' => 'GENERAL OBSERVATION',                           'personnel_type' => 'teaching',     'evaluator_group' => 'self'],

            // ----------------------------------------------------------------
            // Peer evaluator - Teaching personnel
            // ----------------------------------------------------------------
            ['id' => 33, 'name' => 'PROFESSIONAL ATTITUDE AND APPEARANCE',           'personnel_type' => 'teaching',     'evaluator_group' => 'peer'],
            ['id' => 34, 'name' => 'KNOWLEDGE OF SUBJECT MATTER',                   'personnel_type' => 'teaching',     'evaluator_group' => 'peer'],
            ['id' => 35, 'name' => 'TEACHING SKILLS',                               'personnel_type' => 'teaching',     'evaluator_group' => 'peer'],
            ['id' => 36, 'name' => 'CLASSROOM MANAGEMENT',                          'personnel_type' => 'teaching',     'evaluator_group' => 'peer'],
            ['id' => 37, 'name' => 'ASSESSMENT OF LEARNING',                        'personnel_type' => 'teaching',     'evaluator_group' => 'peer'],
            ['id' => 38, 'name' => 'GENERAL OBSERVATION',                           'personnel_type' => 'teaching',     'evaluator_group' => 'peer'],

            // ----------------------------------------------------------------
            // Dean/Head additional criteria - Teaching personnel
            // ----------------------------------------------------------------
            ['id' => 39, 'name' => 'ADMINISTRATIVE/SUPERVISORY COMPETENCE',          'personnel_type' => 'teaching',     'evaluator_group' => 'dean'],
            ['id' => 40, 'name' => 'INSTRUCTIONAL LEADERSHIP',                      'personnel_type' => 'teaching',     'evaluator_group' => 'dean'],
            ['id' => 41, 'name' => 'PERSONAL/ PROFESSIONAL RELATIONSHIPS WITH PERSONNEL', 'personnel_type' => 'teaching', 'evaluator_group' => 'dean'],

            // ----------------------------------------------------------------
            // Student additional criteria - Teaching personnel
            // ----------------------------------------------------------------
            ['id' => 42, 'name' => 'INTERPERSONAL RELATIONSHIP WITH STUDENTS',       'personnel_type' => 'teaching',     'evaluator_group' => 'student'],
        ];

        foreach ($criteria as $criterion) {
            $evaluatorGroup  = $criterion['evaluator_group'];
            $personnelType   = $criterion['personnel_type'];
            unset($criterion['evaluator_group'], $criterion['personnel_type']);

            $created = Criterion::create($criterion);
            $created->evaluatorGroups()->create(['evaluator_group' => $evaluatorGroup]);
            $created->personnelTypes()->create(['personnel_type' => $personnelType]);
        }
    }
}
