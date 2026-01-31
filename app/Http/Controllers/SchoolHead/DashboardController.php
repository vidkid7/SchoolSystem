<?php

namespace App\Http\Controllers\SchoolHead;

use DB, Auth;
use Carbon\Carbon;
use App\Models\Unit;
use App\Models\User;
use App\Models\Staff;
use App\Models\School;
use App\Models\Stock;
use App\Models\Student;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\HeadTeacherLog;
use App\Models\StaffAttendance;
use App\Models\StudentAttendance;
use App\Http\Services\UserService;
use App\Http\Controllers\Controller;
use App\Http\Services\SchoolService;
use App\Http\Services\DashboardService;
use Anuzpandey\LaravelNepaliDate\LaravelNepaliDate;

class DashboardController extends Controller
{
    protected $dashboardService;
    protected $schoolService;

    public function __construct(DashboardService $dashboardService, SchoolService $schoolService)
    {
        $this->dashboardService = $dashboardService;
        $this->schoolService = $schoolService;
    }

    public function index()
    {
        $page_title = Auth::user()->getRoleNames()[0] . ' ' . "Dashboard";
        $schoolId = Auth::user()->school_id;

        // Counts scoped to this head's school
        $totalStudents = Student::where('school_id', $schoolId)->count();
        $totalGirls = Student::where('school_id', $schoolId)
            ->whereHas('user', fn($q) => $q->where('gender', 'female'))
            ->count();
        $totalBoys = Student::where('school_id', $schoolId)
            ->whereHas('user', fn($q) => $q->where('gender', 'male'))
            ->count();

        $today = Carbon::today()->format('Y-m-d');
        $nepaliDateToday = LaravelNepaliDate::from($today)->toNepaliDate();

        $presentStudents = StudentAttendance::where('attendance_type_id', 1)
            ->whereHas('student', fn($q) => $q->where('school_id', $schoolId))
            ->where('date', $nepaliDateToday)
            ->count();
        $absentStudents = StudentAttendance::where('attendance_type_id', 2)
            ->whereHas('student', fn($q) => $q->where('school_id', $schoolId))
            ->where('date', $nepaliDateToday)
            ->count();

        $totalStaffs = Staff::where('school_id', $schoolId)->count();
        $presentStaffs = StaffAttendance::where('attendance_type_id', 1)
            ->whereHas('staff', fn($q) => $q->where('school_id', $schoolId))
            ->where('date', $nepaliDateToday)
            ->count();
        $absentStaffs = StaffAttendance::where('attendance_type_id', 2)
            ->whereHas('staff', fn($q) => $q->where('school_id', $schoolId))
            ->where('date', $nepaliDateToday)
            ->count();

        $major_incidents = HeadTeacherLog::where('school_id', $schoolId)
            ->where('logged_date', $nepaliDateToday)
            ->count();
        $todays_major_incidents = HeadTeacherLog::where('school_id', $schoolId)
            ->where('logged_date', $nepaliDateToday)
            ->get(['major_incidents', 'school_id']);

        // School data for this head's school only
        $school = School::find($schoolId);
        $schoolData = [];
        if ($school) {
            $totalStudentsInSchool = Student::where('school_id', $schoolId)->count();
            $presentStudentsInSchool = StudentAttendance::where('attendance_type_id', 1)
                ->whereHas('student', fn($q) => $q->where('school_id', $schoolId))
                ->where('date', $nepaliDateToday)
                ->count();
            $absentStudentsInSchool = StudentAttendance::where('attendance_type_id', 2)
                ->whereHas('student', fn($q) => $q->where('school_id', $schoolId))
                ->where('date', $nepaliDateToday)
                ->count();
            $totalStaffsInSchool = Staff::where('school_id', $schoolId)->count();
            $presentStaffsInSchool = StaffAttendance::where('attendance_type_id', 1)
                ->whereHas('staff', fn($q) => $q->where('school_id', $schoolId))
                ->where('date', $nepaliDateToday)
                ->count();
            $absentStaffsInSchool = StaffAttendance::where('attendance_type_id', 2)
                ->whereHas('staff', fn($q) => $q->where('school_id', $schoolId))
                ->where('date', $nepaliDateToday)
                ->count();
            $schoolData[] = [
                'school_id' => $school->id,
                'school_name' => $school->name,
                'school_address' => $school->address ?? '',
                'total_students' => $totalStudentsInSchool,
                'present_students' => $presentStudentsInSchool,
                'absent_students' => $absentStudentsInSchool,
                'total_staffs' => $totalStaffsInSchool,
                'present_staffs' => $presentStaffsInSchool,
                'absent_staffs' => $absentStaffsInSchool,
            ];
        }

        $totalSchools = 1; // Head sees one school
        $school_students = $this->schoolService->getSchoolStudent();
        $school_students_count = $this->schoolWiseCountOfStudent($school_students);
        $school_staffs = $this->schoolService->getSchoolStaff();
        $school_staffs_count = $this->schoolWiseCountOfStaff($school_staffs);
        $school_wise_student_attendences = $this->schoolService->getSchoolWiseStudentAttendence();
        $schoolWiseStudentData = $this->getSchoolWiseStudentData($schoolId);
        $schools_wise_reports = collect();

        return view('backend.municipality_admin.dashboard.dashboard', [
            'page_title' => $page_title,
            'presentStudents' => $presentStudents,
            'totalStudents' => $totalStudents,
            'absentStudents' => $absentStudents,
            'totalStaffs' => $totalStaffs,
            'presentStaffs' => $presentStaffs,
            'absentStaffs' => $absentStaffs,
            'schoolData' => $schoolData,
            'major_incidents' => $major_incidents,
            'totalSchools' => $totalSchools,
            'totalGirls' => $totalGirls,
            'totalBoys' => $totalBoys,
            'todays_major_incidents' => $todays_major_incidents,
            'school_staffs ' => $school_staffs,
            'school_staffs_count' => $school_staffs_count,
            'school_students_count' => $school_students_count,
            'school_wise_student_attendences' => $school_wise_student_attendences,
            'schoolWiseStudentData' => $schoolWiseStudentData,
            'schools_wise_reports' => $schools_wise_reports,
        ]);
    }

    private function getSchoolWiseStudentData($schoolId = null)
    {
        $query = Student::join('schools', 'students.school_id', '=', 'schools.id')
            ->select('schools.name as school_name', DB::raw('count(students.id) as total_students'));
        if ($schoolId) {
            $query->where('schools.id', $schoolId);
        }
        $schoolWiseStudents = $query->groupBy('schools.name')->get()->toArray();

        $labels = array_column($schoolWiseStudents, 'school_name');
        $dataValues = array_column($schoolWiseStudents, 'total_students');

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'School wise Student Count',
                    'data' => $dataValues,
                    'borderWidth' => 1
                ]
            ]
        ];
    }

    public function schoolWiseCountOfStudent($originalData)
    {
        $labels = [];
        $data = [];
        foreach ($originalData as $item) {
            $labels[] = $item['name'];
            $data[] = $item['total_student'];
        }
        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'School wise Student Count', 'data' => $data, 'borderWidth' => 1]
            ]
        ];
    }

    public function schoolWiseCountOfStaff($originalData)
    {
        $labels = [];
        $data = [];
        foreach ($originalData as $item) {
            $labels[] = $item['name'];
            $data[] = $item['total_staffs'];
        }
        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'School wise Staffs Count', 'data' => $data, 'borderWidth' => 1]
            ]
        ];
    }
}
