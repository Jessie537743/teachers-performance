<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        // Each entry: [question_id, criteria_id, question_text]
        // The question_id column is the auto-increment PK; we insert in order
        // so the IDs will match the specification naturally.

        $questions = [
            // ================================================================
            // CRITERIA 1 - Student / Professional Attitude & Appearance (12 Qs)
            // IDs 1-14 per spec (note: IDs 1,2,5-14 map here; 3,4 map to c2)
            // We insert them sequentially; the DB assigns IDs 1-12 for c1.
            // Spec assigns explicit IDs so we honour the order given.
            // ================================================================

            // --- Criteria 1: Student - Professional Attitude (12 questions) ---
            [1,  'Shows marked extraordinary enthusiasm about his/her teaching'],
            [1,  'Endeavors to implement the school\'s objective'],
            [1,  'Intellectually humble and tolerant'],
            [1,  'Always clean and orderly in person, dress, and habits'],
            [1,  'Well-modulated voice'],
            [1,  'Capable of adjusting to changing conditions and situations'],
            [1,  'Consistently alert and emotionally mature'],
            [1,  'Punctual in class attendance/meetings and other school activities'],
            [1,  'Follow school rules and regulations'],
            [1,  'Performs other duties assigned outside of classroom work'],
            [1,  'The instructor explains lessons clearly.'],
            [1,  'The instructor demonstrates mastery of the subject.'],

            // --- Criteria 2: Student - Knowledge of Subject Matter (9 questions) ---
            [2,  'The instructor communicates respectfully with students.'],
            [2,  'The instructor encourages participation.'],
            [2,  'Prepares lesson well'],
            [2,  'Has ample understanding/grasp of subject'],
            [2,  'Shows interest in subject matter'],
            [2,  'Welcomes questions/request/clarification'],
            [2,  'Organizes subject matter well'],
            [2,  'Selects relevant material effectively'],
            [2,  'Ability to relate subject matter to other fields'],

            // --- Criteria 3: Student - Teaching Skills (9 questions) ---
            [3,  'Speaks clearly and distinctly'],
            [3,  'Speaks English-Filipino correctly'],
            [3,  'Makes lesson interesting'],
            [3,  'Explains subject matter clearly'],
            [3,  'Makes subject matter relevant to the course objective'],
            [3,  'Makes subject matter relevant/practical to current needs'],
            [3,  'Uses techniques for student\'s participation'],
            [3,  'Encourages critical thinking'],
            [3,  'Provides appropriate drills/seatworks/assignments'],

            // --- Criteria 4: Student - Classroom Management (4 questions) ---
            [4,  'Commands student\'s respect'],
            [4,  'Handles individual/group discipline tactfully'],
            [4,  'Fair in dealing with students'],
            [4,  'Adopts a system in routine work'],

            // --- Criteria 5: Student - Assessment of Learning (6 questions) ---
            [5,  'Assigns assessment that is related to subject/course material'],
            [5,  'Allows enough time to complete the assigned assessment'],
            [5,  'Give examinations that reflected the material covered in the Delivery Instructions'],
            [5,  'Provides constructive and timely feedbacks on a graded Material'],
            [5,  'Grades the assigned assessment fairly by using rubrics'],
            [5,  'Creative in developing activities and other formative Assessments'],

            // --- Criteria 6: Student - General Observation (4 questions) ---
            [6,  'Rapport between teachers and students'],
            [6,  'Class Participation'],
            [6,  'Overall Teacher Impact'],
            [6,  'General Online Class Condition'],

            // ================================================================
            // CRITERIA 7-13: Student / Non-teaching (19 questions total)
            // ================================================================

            // --- Criteria 7: Student - Quality of Work (3 questions) ---
            [7,  'Produces work that meets expected standards of quality'],
            [7,  'Demonstrates accuracy and attention to detail'],
            [7,  'Submits work that is complete and well-presented'],

            // --- Criteria 8: Student - Quantity of Work (3 questions) ---
            [8,  'Accomplishes required volume of work within set timeframes'],
            [8,  'Manages workload effectively and efficiently'],
            [8,  'Consistently meets productivity expectations'],

            // --- Criteria 9: Student - Knowledge of Work (3 questions) ---
            [9,  'Demonstrates adequate knowledge of assigned duties'],
            [9,  'Applies relevant skills and expertise in performing tasks'],
            [9,  'Keeps updated with developments relevant to the job'],

            // --- Criteria 10: Student - Reliability (3 questions) ---
            [10, 'Can be counted on to fulfill commitments'],
            [10, 'Delivers consistent results with minimal supervision'],
            [10, 'Follows through on responsibilities dependably'],

            // --- Criteria 11: Student - Cooperation (3 questions) ---
            [11, 'Works well with others to achieve shared goals'],
            [11, 'Contributes positively to team efforts'],
            [11, 'Shows willingness to assist colleagues when needed'],

            // --- Criteria 12: Student - Initiative (2 questions) ---
            [12, 'Takes proactive steps to address challenges'],
            [12, 'Suggests and implements improvements without being asked'],

            // --- Criteria 13: Student - Initiative (2 questions) ---
            [13, 'Demonstrates resourcefulness in solving work-related problems'],
            [13, 'Volunteers for additional responsibilities when appropriate'],

            // ================================================================
            // CRITERIA 14-19: Dean/Head / Teaching (44 questions)
            // Same question texts as criteria 1-6, different evaluator group
            // ================================================================

            // --- Criteria 14: Dean - Professional Attitude & Appearance (12 questions) ---
            [14, 'Shows marked extraordinary enthusiasm about his/her teaching'],
            [14, 'Endeavors to implement the school\'s objective'],
            [14, 'Intellectually humble and tolerant'],
            [14, 'Always clean and orderly in person, dress, and habits'],
            [14, 'Well-modulated voice'],
            [14, 'Capable of adjusting to changing conditions and situations'],
            [14, 'Consistently alert and emotionally mature'],
            [14, 'Punctual in class attendance/meetings and other school activities'],
            [14, 'Follow school rules and regulations'],
            [14, 'Performs other duties assigned outside of classroom work'],
            [14, 'The instructor explains lessons clearly.'],
            [14, 'The instructor demonstrates mastery of the subject.'],

            // --- Criteria 15: Dean - Knowledge of Subject Matter (9 questions) ---
            [15, 'The instructor communicates respectfully with students.'],
            [15, 'The instructor encourages participation.'],
            [15, 'Prepares lesson well'],
            [15, 'Has ample understanding/grasp of subject'],
            [15, 'Shows interest in subject matter'],
            [15, 'Welcomes questions/request/clarification'],
            [15, 'Organizes subject matter well'],
            [15, 'Selects relevant material effectively'],
            [15, 'Ability to relate subject matter to other fields'],

            // --- Criteria 16: Dean - Teaching Skills (9 questions) ---
            [16, 'Speaks clearly and distinctly'],
            [16, 'Speaks English-Filipino correctly'],
            [16, 'Makes lesson interesting'],
            [16, 'Explains subject matter clearly'],
            [16, 'Makes subject matter relevant to the course objective'],
            [16, 'Makes subject matter relevant/practical to current needs'],
            [16, 'Uses techniques for student\'s participation'],
            [16, 'Encourages critical thinking'],
            [16, 'Provides appropriate drills/seatworks/assignments'],

            // --- Criteria 17: Dean - Classroom Management (4 questions) ---
            [17, 'Commands student\'s respect'],
            [17, 'Handles individual/group discipline tactfully'],
            [17, 'Fair in dealing with students'],
            [17, 'Adopts a system in routine work'],

            // --- Criteria 18: Dean - Assessment of Learning (6 questions) ---
            [18, 'Assigns assessment that is related to subject/course material'],
            [18, 'Allows enough time to complete the assigned assessment'],
            [18, 'Give examinations that reflected the material covered in the Delivery Instructions'],
            [18, 'Provides constructive and timely feedbacks on a graded Material'],
            [18, 'Grades the assigned assessment fairly by using rubrics'],
            [18, 'Creative in developing activities and other formative Assessments'],

            // --- Criteria 19: Dean - General Observation (4 questions) ---
            [19, 'Rapport between teachers and students'],
            [19, 'Class Participation'],
            [19, 'Overall Teacher Impact'],
            [19, 'General Online Class Condition'],

            // ================================================================
            // CRITERIA 20-26: Dean/Head / Non-teaching (19 questions)
            // Same texts as criteria 7-13
            // ================================================================

            // --- Criteria 20: Dean - Quality of Work (3 questions) ---
            [20, 'Produces work that meets expected standards of quality'],
            [20, 'Demonstrates accuracy and attention to detail'],
            [20, 'Submits work that is complete and well-presented'],

            // --- Criteria 21: Dean - Quantity of Work (3 questions) ---
            [21, 'Accomplishes required volume of work within set timeframes'],
            [21, 'Manages workload effectively and efficiently'],
            [21, 'Consistently meets productivity expectations'],

            // --- Criteria 22: Dean - Knowledge of Work (3 questions) ---
            [22, 'Demonstrates adequate knowledge of assigned duties'],
            [22, 'Applies relevant skills and expertise in performing tasks'],
            [22, 'Keeps updated with developments relevant to the job'],

            // --- Criteria 23: Dean - Reliability (3 questions) ---
            [23, 'Can be counted on to fulfill commitments'],
            [23, 'Delivers consistent results with minimal supervision'],
            [23, 'Follows through on responsibilities dependably'],

            // --- Criteria 24: Dean - Cooperation (3 questions) ---
            [24, 'Works well with others to achieve shared goals'],
            [24, 'Contributes positively to team efforts'],
            [24, 'Shows willingness to assist colleagues when needed'],

            // --- Criteria 25: Dean - Initiative (2 questions) ---
            [25, 'Takes proactive steps to address challenges'],
            [25, 'Suggests and implements improvements without being asked'],

            // --- Criteria 26: Dean - Initiative (2 questions) ---
            [26, 'Demonstrates resourcefulness in solving work-related problems'],
            [26, 'Volunteers for additional responsibilities when appropriate'],

            // ================================================================
            // CRITERIA 27-32: Self / Teaching (44 questions)
            // Same question texts as criteria 1-6
            // ================================================================

            // --- Criteria 27: Self - Professional Attitude & Appearance (12 questions) ---
            [27, 'Shows marked extraordinary enthusiasm about his/her teaching'],
            [27, 'Endeavors to implement the school\'s objective'],
            [27, 'Intellectually humble and tolerant'],
            [27, 'Always clean and orderly in person, dress, and habits'],
            [27, 'Well-modulated voice'],
            [27, 'Capable of adjusting to changing conditions and situations'],
            [27, 'Consistently alert and emotionally mature'],
            [27, 'Punctual in class attendance/meetings and other school activities'],
            [27, 'Follow school rules and regulations'],
            [27, 'Performs other duties assigned outside of classroom work'],
            [27, 'The instructor explains lessons clearly.'],
            [27, 'The instructor demonstrates mastery of the subject.'],

            // --- Criteria 28: Self - Knowledge of Subject Matter (9 questions) ---
            [28, 'The instructor communicates respectfully with students.'],
            [28, 'The instructor encourages participation.'],
            [28, 'Prepares lesson well'],
            [28, 'Has ample understanding/grasp of subject'],
            [28, 'Shows interest in subject matter'],
            [28, 'Welcomes questions/request/clarification'],
            [28, 'Organizes subject matter well'],
            [28, 'Selects relevant material effectively'],
            [28, 'Ability to relate subject matter to other fields'],

            // --- Criteria 29: Self - Teaching Skills (9 questions) ---
            [29, 'Speaks clearly and distinctly'],
            [29, 'Speaks English-Filipino correctly'],
            [29, 'Makes lesson interesting'],
            [29, 'Explains subject matter clearly'],
            [29, 'Makes subject matter relevant to the course objective'],
            [29, 'Makes subject matter relevant/practical to current needs'],
            [29, 'Uses techniques for student\'s participation'],
            [29, 'Encourages critical thinking'],
            [29, 'Provides appropriate drills/seatworks/assignments'],

            // --- Criteria 30: Self - Classroom Management (4 questions) ---
            [30, 'Commands student\'s respect'],
            [30, 'Handles individual/group discipline tactfully'],
            [30, 'Fair in dealing with students'],
            [30, 'Adopts a system in routine work'],

            // --- Criteria 31: Self - Assessment of Learning (6 questions) ---
            [31, 'Assigns assessment that is related to subject/course material'],
            [31, 'Allows enough time to complete the assigned assessment'],
            [31, 'Give examinations that reflected the material covered in the Delivery Instructions'],
            [31, 'Provides constructive and timely feedbacks on a graded Material'],
            [31, 'Grades the assigned assessment fairly by using rubrics'],
            [31, 'Creative in developing activities and other formative Assessments'],

            // --- Criteria 32: Self - General Observation (4 questions) ---
            [32, 'Rapport between teachers and students'],
            [32, 'Class Participation'],
            [32, 'Overall Teacher Impact'],
            [32, 'General Online Class Condition'],

            // ================================================================
            // CRITERIA 33-38: Peer / Teaching (44 questions)
            // Same question texts as criteria 1-6
            // ================================================================

            // --- Criteria 33: Peer - Professional Attitude & Appearance (12 questions) ---
            [33, 'Shows marked extraordinary enthusiasm about his/her teaching'],
            [33, 'Endeavors to implement the school\'s objective'],
            [33, 'Intellectually humble and tolerant'],
            [33, 'Always clean and orderly in person, dress, and habits'],
            [33, 'Well-modulated voice'],
            [33, 'Capable of adjusting to changing conditions and situations'],
            [33, 'Consistently alert and emotionally mature'],
            [33, 'Punctual in class attendance/meetings and other school activities'],
            [33, 'Follow school rules and regulations'],
            [33, 'Performs other duties assigned outside of classroom work'],
            [33, 'The instructor explains lessons clearly.'],
            [33, 'The instructor demonstrates mastery of the subject.'],

            // --- Criteria 34: Peer - Knowledge of Subject Matter (9 questions) ---
            [34, 'The instructor communicates respectfully with students.'],
            [34, 'The instructor encourages participation.'],
            [34, 'Prepares lesson well'],
            [34, 'Has ample understanding/grasp of subject'],
            [34, 'Shows interest in subject matter'],
            [34, 'Welcomes questions/request/clarification'],
            [34, 'Organizes subject matter well'],
            [34, 'Selects relevant material effectively'],
            [34, 'Ability to relate subject matter to other fields'],

            // --- Criteria 35: Peer - Teaching Skills (9 questions) ---
            [35, 'Speaks clearly and distinctly'],
            [35, 'Speaks English-Filipino correctly'],
            [35, 'Makes lesson interesting'],
            [35, 'Explains subject matter clearly'],
            [35, 'Makes subject matter relevant to the course objective'],
            [35, 'Makes subject matter relevant/practical to current needs'],
            [35, 'Uses techniques for student\'s participation'],
            [35, 'Encourages critical thinking'],
            [35, 'Provides appropriate drills/seatworks/assignments'],

            // --- Criteria 36: Peer - Classroom Management (4 questions) ---
            [36, 'Commands student\'s respect'],
            [36, 'Handles individual/group discipline tactfully'],
            [36, 'Fair in dealing with students'],
            [36, 'Adopts a system in routine work'],

            // --- Criteria 37: Peer - Assessment of Learning (6 questions) ---
            [37, 'Assigns assessment that is related to subject/course material'],
            [37, 'Allows enough time to complete the assigned assessment'],
            [37, 'Give examinations that reflected the material covered in the Delivery Instructions'],
            [37, 'Provides constructive and timely feedbacks on a graded Material'],
            [37, 'Grades the assigned assessment fairly by using rubrics'],
            [37, 'Creative in developing activities and other formative Assessments'],

            // --- Criteria 38: Peer - General Observation (4 questions) ---
            [38, 'Rapport between teachers and students'],
            [38, 'Class Participation'],
            [38, 'Overall Teacher Impact'],
            [38, 'General Online Class Condition'],

            // ================================================================
            // CRITERIA 39: Dean - Administrative/Supervisory Competence (15 Qs)
            // ================================================================
            [39, 'Plans activities and budget'],
            [39, 'Sets priorities and manages time effectively'],
            [39, 'Monitors and evaluates college activities'],
            [39, 'Resolves conflicts and problems effectively'],
            [39, 'Delegates authority and responsibility'],
            [39, 'Makes timely and sound decisions'],
            [39, 'Organizes and coordinates college activities'],
            [39, 'Implements policies and procedures'],
            [39, 'Communicates effectively with stakeholders'],
            [39, 'Maintains accurate records and reports'],
            [39, 'Ensures compliance with regulatory requirements'],
            [39, 'Promotes professional development of personnel'],
            [39, 'Builds and maintains positive working relationships'],
            [39, 'Demonstrates ethical behavior and integrity'],
            [39, 'Adapts to changes and innovations'],

            // ================================================================
            // CRITERIA 40: Dean - Instructional Leadership (19 questions)
            // ================================================================
            [40, 'Provides clear direction for instruction'],
            [40, 'Ensures curriculum alignment'],
            [40, 'Supports innovative teaching methodologies'],
            [40, 'Monitors instructional quality'],
            [40, 'Provides constructive feedback to faculty'],
            [40, 'Promotes use of technology in teaching'],
            [40, 'Supports assessment and evaluation practices'],
            [40, 'Encourages research and scholarly activities'],
            [40, 'Facilitates professional learning communities'],
            [40, 'Promotes student-centered learning'],
            [40, 'Ensures adequate learning resources'],
            [40, 'Supports faculty in curriculum development'],
            [40, 'Monitors student academic performance'],
            [40, 'Promotes inclusive education practices'],
            [40, 'Supports community engagement activities'],
            [40, 'Ensures quality assurance in instruction'],
            [40, 'Facilitates accreditation compliance'],
            [40, 'Promotes interdisciplinary collaboration'],
            [40, 'Supports mentoring programs'],

            // ================================================================
            // CRITERIA 41: Dean - Professional Relationships with Personnel (12 Qs)
            // ================================================================
            [41, 'Maintains professional relationships with faculty'],
            [41, 'Communicates respectfully with personnel'],
            [41, 'Values diversity and inclusiveness'],
            [41, 'Supports team building activities'],
            [41, 'Handles personnel concerns fairly'],
            [41, 'Maintains confidentiality of personnel matters'],
            [41, 'Recognizes personnel achievements'],
            [41, 'Promotes positive work environment'],
            [41, 'Supports personnel welfare programs'],
            [41, 'Facilitates open communication channels'],
            [41, 'Mediates conflicts professionally'],
            [41, 'Promotes collaborative decision making'],

            // ================================================================
            // CRITERIA 42: Student - Interpersonal Relationship with Students (6 Qs)
            // ================================================================
            [42, 'Maintains approachable and friendly demeanor'],
            [42, 'Shows genuine concern for student welfare'],
            [42, 'Listens actively to student concerns'],
            [42, 'Provides timely response to student needs'],
            [42, 'Respects student diversity and individuality'],
            [42, 'Creates a supportive learning environment'],
        ];

        foreach ($questions as [$criteriaId, $questionText]) {
            Question::create([
                'criteria_id'   => $criteriaId,
                'question_text' => $questionText,
            ]);
        }
    }
}
