<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Auth;
use Carbon\Carbon;
use App\DivisionTeacher;
use App\Subject;
use App\Application;
use App\Division;
use App\Teacher;
use App\User;
use App\Mail\Email;
use Illuminate\Support\Facades\Mail;
use App\InternalTest;
class TeachersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function showClassesSubjects()
    {
        $user = Auth::user();
        $detail = DivisionTeacher::where('teacher_id',$user->id)->get();
        for($i=0;$i<count($detail);$i++)
        {
            $detail[$i]['div_name'] = Division::where('id',$detail[$i]['division_id'])->first()['class'];
            $detail[$i]['subject_name'] = Subject::where('id',$detail[$i]['subject_id'])->first()['subject'];
        }
        return view('Teacher.home')->with('detail',$detail);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function createSessionForTeacher(Request $request)
    {
        $teacher = Auth::user();
        $request->session()->put('subject_no'.$teacher->id,$request['subject_no']);
        $request->session()->put('division_no'.$teacher->id,$request['division_id']);
        $request->session()->put('test_no'.$teacher->id,$request['test_no']);
        return redirect('teacher/studentmarks');
    }
    public function showStudents()
    {
        $teacher = Auth::user();
        $subject_id = session()->get('subject_no'.$teacher->id,'Error');
        $test_no = session()->get('test_no'.$teacher->id,'Error');
        $students = User::where('division',session()->get('division_no'.$teacher->id,'Error'))->orderBy('roll_no')                       
                            ->get();
        $marks = InternalTest::where('division_id',session()->get('division_no'.$teacher->id,'Error'))
                            ->where('subject_id',$subject_id)
                            ->orderBy('roll_no')                       
                            ->get();
        if(isset($marks) && $test_no == 1)
        {
        }
        
        $search = $test_no==1?'Expiry_1':'Expiry_2';
        $exists = DivisionTeacher::where('division_id',session()->get('division_no'.$teacher->id,'Error'))
                                ->where('subject_id',$subject_id)->value($search);
        $flag = isset($exists);
        return view('Teacher.putMarks')->with('students',$students)->with('test_no',$test_no)->with('flag',$flag);
    }
    public function showTestThree()
    {
        $teacher = Auth::user();
        $students = Application::where('status','1')->where('teacher_id',$teacher->id)->with('user:id,name,roll_no,division')->with('division')->with('subject:id,subject')->get();
        // return $students;
        return view('Teacher.test3')->with('students',$students);
    }
    public function storeTestThree(Request $request)
    {
        $teacher = Auth::user();
        $students = Application::where('teacher_id',$teacher->id);
        $data = array();
        foreach($_POST as $roll=>$mark)
        { 
            if(is_numeric($roll))
            {
               
                 $data[$roll] = $mark;
            }
        }
        $this->updateTestThreeValues($data);
        return redirect('/teacher/test3')->with('success','Marks Entered successfully!');
    }
    public static function updateTestThreeValues(array $values)
    {
        $table = Application::getModel()->getTable();
        $cases = [];
        $ids = [];
        foreach ($values as $id => $value) {
            $id = (int) $id;
            $cases[] = "WHEN {$id} then $value";
            $ids[] = $id;
        }
        $ids = implode(',', $ids);
        $cases = implode(' ', $cases);
        return \DB::update("UPDATE `{$table}` SET `marks` = CASE `id` {$cases} END WHERE `id` in ({$ids})");
    }
    // public static function updateTestTwoValues(array $values)
    // {
    //     $table = Internal::getModel()->getTable();
    //     $cases = [];
    //     $ids = [];
    //     foreach ($values as $id => $value) {
    //         $id = (int) $id;
    //         $cases[] = "WHEN {$id} then $value";
    //         $ids[] = $id;
    //     }
    //     $ids = implode(',', $ids);
    //     $cases = implode(' ', $cases);
    //     return \DB::update("UPDATE `{$table}` SET `marks` = CASE `id` {$cases} END WHERE `id` in ({$ids})");
    // }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if($request->session()->get('test_no'.$user->id) == '1')
        {
            foreach($_POST as $roll=>$mark)
            {
                if(is_numeric($roll))
                InternalTest::updateOrCreate
                (
                    [
                    'division_id' =>$request->session()->get('division_no'.$user->id,'Error'),
                    'roll_no' => $roll,
                    'subject_id' => $request->session()->get('subject_no'.$user->id,'Error')
                ],
                    [
                        'ia1' => $mark,
                ]
            );
            }
            $divtoteacher = DivisionTeacher::where('teacher_id',$user->id)
                                            ->where('division_id',$request->session()->get('division_no'.$user->id,'Error'))
                                            ->where('subject_id',$request->session()->get('subject_no'.$user->id,'Error'))->first();
            $divtoteacher->Expiry_1 = now()->addHours(48);
            $divtoteacher->save();
        }
        else if($request->session()->get('test_no'.$user->id) == '2')
        {
            foreach($_POST as $roll=>$mark)
            {
                if(is_numeric($roll))
                {
                    $data=InternalTest::where('division_id',session()->get('division_no'.$user->id,'Error'))->where('roll_no',$roll)->where('subject_id',session()->get('subject_no'.$user->id,'Error'))->first();
                    $data->IA2=$mark;
                    $data->Avg = ceil(($data->IA1 + $mark)/2);
                        if($data->IA2 == -2 || $data->IA1 == -2)
                        {
                            $data->Avg = 0;
                        }
                    $data->save();
                }
            }
            $divtoteacher = DivisionTeacher::where('teacher_id',$user->id)
                                             ->where('division_id',$request->session()->get('division_no'.$user->id,'Error'))
                                             ->where('subject_id',$request->session()->get('subject_no'.$user->id,'Error'))->first();
            $divtoteacher->Expiry_2 = now()->addHours(48);
            $divtoteacher->save();  
        }
        $subject_no = $request->session()->get('subject_no'.$user->id,'Error');
        $subject = Subject::where('id',$subject_no)->first()['subject'];
        $division = Division::where('id',session()->get('division_no'.$user->id,'Error'))->first();
        $request->session()->forget(['division_no'.$user->id, 'subject_no'.$user->id,'test_no'.$user->id]);
        return redirect('teacher\putmarks');
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function checkstatus()
    {
        $user = Auth::user();
        $detail = DivisionTeacher::where('teacher_id' , $user->id)->get();
        for($i=0;$i<count($detail);$i++)
        {
            $detail[$i]['div_name'] = Division::where('id',$detail[$i]['division_id'])->first()['class'];
            $detail[$i]['subject_name'] = Subject::where('id',$detail[$i]['subject_id'])->first()['subject'];
        }
        return view('Teacher.checkstatus')->with('detail',$detail);
    }
    public function send($subject,Division $division)
    {
        $teacher = Auth::user();
        Mail::to($division['email'])
        ->cc($teacher->email)
        ->send(new Email($subject , $division));
        return $this->index();
    }
    public function status(Request $request)
    {
        $teacher = Auth::user();
        $request->session()->put('subject_no'.$teacher->id,$request['subject_no']);
        $request->session()->put('division_no'.$teacher->id,$request['division_id']);
        $request->session()->put('test_no'.$teacher->id,$request['test_no']);
        return redirect('teacher/status');
    }
    public function showStatus()
    {
        $teacher = Auth::user();
        $test_no = session()->get('test_no'.$teacher->id,"Error");
        $students = InternalTest::where('division_id',session()->get('division_no'.$teacher->id,'Error'))
                                    ->where('subject_id',session()->get('subject_no'.$teacher->id,'Error'))
                                    ->orderBy('roll_no')->get();
        for($i=0;$i<count($students);$i++)
        {
            $students[$i]['name'] = User::where('division',session()->get('division_no'.$teacher->id,'Error'))->where('roll_no',$students[$i]['roll_no'])->first()['name'];
        }
        session()->forget(['division_no'.$teacher->id, 'subject_no'.$teacher->id,'test_no'.$teacher->id]);
        return view('Teacher.status')->with('students',$students)->with('test_no',$test_no);
    }
    public function show($id)
    {
    }
    public function marks(Request $request)
    {
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}