<?php

namespace App\Http\Controllers\DistrictAdmin;

use DB, Auth;
use Carbon\Carbon;
use App\Models\Staff;
use App\Models\School;
use App\Models\Student;
use App\Models\HeadTeacherLog;
use App\Models\StaffAttendance;
use App\Models\StudentAttendance;
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
        $districtId = Auth::user()->district_id;

        // Counts scoped to this district's schools
        $schoolIds = School::where('district_id', $districtId)->pluck('id');
        $totalStudents = Student::whereIn('school_id', $schoolIds)->count();
        $totalGirls = Student::whereIn('school_id', $schoolIds)
            ->whereHas('user', fn($q) => $q->where('gender', 'female'))
            ->count();
        $totalBoys = Student::whereIn('school_id', $schoolIds)
            ->whereHas('user', fn($q) => $q->where('gender', 'male'))
            ->count();

        $today = Carbon::today()->format('Y-m-d');
        $nepaliDateToday = LaravelNepaliDate::from($today)->toNepaliDate();

        $presentStudents = StudentAttendance::where('attendance_type_id', 1)
            ->whereHas('student', fn($q) => $q->whereIn('school_id', $schoolIds))
            ->where('date', $nepaliDateToday)
            ->count();
        $absentStudents = StudentAttendance::where('attendance_type_id', 2)
            ->whereHas('student', fn($q) => $q->whereIn('school_id', $schoolIds))
            ->where('date', $nepaliDateToday)
            ->count();

        $totalStaffs = Staff::whereIn('school_id', $schoolIds)->count();
        $presentStaffs = StaffAttendance::where('attendance_type_id', 1)
            ->whereHas('staff', fn($q) => $q->whereIn('school_id', $schoolIds))
            ->where('date', $nepaliDateToday)
            ->count();
        $absentStaffs = StaffAttendance::where('attendance_type_id', 2)
            ->whereHas('staff', fn($q) => $q->whereIn('school_id', $schoolIds))
            ->where('date', $nepaliDateToday)
            ->count();

        $major_incidents = HeadTeacherLog::whereIn('school_id', $schoolIds)
            ->where('logged_date', $nepaliDateToday)
            ->count();
        $todays_major_incidents = HeadTeacherLog::whereIn('school_id', $schoolIds)
            ->where('logged_date', $nepaliDateToday)
            ->get(['major_incidents', 'school_id']);

        $schools = School::where('district_id', $districtId)->get();
        $schoolData = [];
        foreach ($schools as $school) {
            $sid = $school->id;
            $schoolData[] = [
                'school_id' => $school->id,
                'school_name' => $school->name,
                'school_address' => $school->address ?? '',
                'total_students' => Student::where('school_id', $sid)->count(),
                'present_students' => StudentAttendance::where('attendance_type_id', 1)->whereHas('student', fn($q) => $q->where('school_id', $sid))->where('date', $nepaliDateToday)->count(),
                'absent_students' => StudentAttendance::where('attendance_type_id', 2)->whereHas('student', fn($q) => $q->where('school_id', $sid))->where('date', $nepaliDateToday)->count(),
                'total_staffs' => Staff::where('school_id', $sid)->count(),
                'present_staffs' => StaffAttendance::where('attendance_type_id', 1)->whereHas('staff', fn($q) => $q->where('school_id', $sid))->where('date', $nepaliDateToday)->count(),
                'absent_staffs' => StaffAttendance::where('attendance_type_id', 2)->whereHas('staff', fn($q) => $q->where('school_id', $sid))->where('date', $nepaliDateToday)->count(),
            ];
        }

        $totalSchools = $schools->count();
        $school_students = $this->schoolService->getSchoolStudent();
        $school_students_count = $this->schoolWiseCountOfStudent($school_students);
        $school_staffs = $this->schoolService->getSchoolStaff();
        $school_staffs_count = $this->schoolWiseCountOfStaff($school_staffs);
        $school_wise_student_attendences = $this->schoolService->getSchoolWiseStudentAttendence();
        $schoolWiseStudentData = $this->getSchoolWiseStudentData($schoolIds->toArray());
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

    private function getSchoolWiseStudentData(array $schoolIds = [])
    {
        $query = Student::join('schools', 'students.school_id', '=', 'schools.id')
            ->select('schools.name as school_name', DB::raw('count(students.id) as total_students'));
        if (!empty($schoolIds)) {
            $query->whereIn('schools.id', $schoolIds);
        }
        $schoolWiseStudents = $query->groupBy('schools.name')->get()->toArray();
        return [
            'labels' => array_column($schoolWiseStudents, 'school_name'),
            'datasets' => [['label' => 'School wise Student Count', 'data' => array_column($schoolWiseStudents, 'total_students'), 'borderWidth' => 1]]
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
        return ['labels' => $labels, 'datasets' => [['label' => 'School wise Student Count', 'data' => $data, 'borderWidth' => 1]]];
    }

    public function schoolWiseCountOfStaff($originalData)
    {
        $labels = [];
        $data = [];
        foreach ($originalData as $item) {
            $labels[] = $item['name'];
            $data[] = $item['total_staffs'];
        }
        return ['labels' => $labels, 'datasets' => [['label' => 'School wise Staffs Count', 'data' => $data, 'borderWidth' => 1]]];
    }
}
