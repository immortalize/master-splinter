<?php

namespace App\Http\Controllers;

use App\Course;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Customer;
use App\Http\Requests\StoreCoursesRequest;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Traits\FileUploadTrait;



class CoursesController extends Controller
{
    use FileUploadTrait;


    public function show($course_slug)
    {
        $course = Course::where('slug', $course_slug)->with('publishedLessons')->firstOrFail();
        $purchased_course = \Auth::check() && $course->students()->where('user_id', \Auth::id())->count() > 0;

        return view('course', compact('course', 'purchased_course'));
    }

    public function payment(Request $request)
    {
        $course = Course::findOrFail($request->get('course_id'));
        $this->createStripeCharge($request);

        $course->students()->attach(\Auth::id());

        return redirect()->back()->with('success', 'Payment completed successfully.');
    }

    private function createStripeCharge($request)
    {
        Stripe::setApiKey(env('STRIPE_API_KEY'));

        try {
            $customer = Customer::create([
                'email' => $request->get('stripeEmail'),
                'source'  => $request->get('stripeToken')
            ]);

            $charge = Charge::create([
                'customer' => $customer->id,
                'amount' => $request->get('amount'),
                'currency' => "usd"
            ]);
        } catch (\Stripe\Error\Base $e) {
            return redirect()->back()->withError($e->getMessage())->send();
        }
    }

    public function rating($course_id, Request $request)
    {
        $course = Course::findOrFail($course_id);
        $course->students()->updateExistingPivot(\Auth::id(), ['rating' => $request->get('rating')]);

        return redirect()->back()->with('success', 'Thank you for rating.');
    }

    /*
        added by bekir.eldem@gmail.com
    */

    /**
     * Show the form for creating new Course.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

                if (! Gate::allows('course_create')) {
                    return abort(401);
                }

        $teachers = \App\User::whereHas('role', function ($q) { $q->where('role_id', 2); } )->get()->pluck('name', 'id');

        return view('courses.create', compact('teachers'));
    }

    /**
     * Store a newly created Course in storage.
     *
     * @param  \App\Http\Requests\StoreCoursesRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCoursesRequest $request)
    {
        if (! Gate::allows('course_create')) {
            return abort(401);
        }
        $request = $this->saveFiles($request);
        // add slug
        $request['slug'] = str_slug($request['title'], '-');

        $course = Course::create($request->all());
        $teachers = \Auth::user()->isAdmin() ? array_filter((array)$request->input('teachers')) : [\Auth::user()->id];
        $course->teachers()->sync($teachers);

        return redirect()->route('courses.index');
    }

    /**
     * Display a listing of Course.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
       $courses = Course::ofTeacher()->get();

        return view('courses.index', compact('courses'));
    }

}
