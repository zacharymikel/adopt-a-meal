<?php

namespace App\Http\Controllers;

use App\Contracts\IMessagesRepository;
use App\Contracts\IVolunteerFormRepository;
use App\Contracts\ICalendarService;
use App\Contracts\IMealIdeaRepository;
use App\Mail\AdminApproveEmail;
use App\Mail\VolunteerApprovedEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use http\Exception;
use Illuminate\Support\Facades\Auth;
use App\Utils;

define('CALENDAR_ID', env('CALENDAR_ID'));
define('CONFIRMED_CALENDAR_ID', env('CONFIRMED_CALENDAR_ID'));

class AdminController extends Controller
{

    protected $formRepository;
    protected $calendarService;
    protected $mealRepository;
    protected $messagesRepository;

    public function __construct(IVolunteerFormRepository $formRepository, ICalendarService $calendarService, IMealIdeaRepository $mealRepository, IMessagesRepository $messagesRepository)
    {
        $this->calendarService = $calendarService;
        $this->formRepository = $formRepository;
        $this->mealRepository = $mealRepository;
        $this->messagesRepository = $messagesRepository;
        $this->middleware('auth');
    }


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin', ['volunteerForms' => $this->formRepository->getAllNewForms()]);
    }

    public function viewVolunteerFormsTable()
    {
        $allVolunteerForms = $this->formRepository->all(); //asort(, function($date1, $date2) {} );
        return view('admin-volunteerforms-table', ['volunteerforms' => $allVolunteerForms ]);
    }

    public function viewMealIdeas()
    {
        return view('admin-mealideas', ['mealideas' => $this->mealRepository->getNewMealIdeas()]);
    }

    public function viewMealIdeasTable()
    {
        return view('admin-mealideas-table', ['mealideas' => $this->mealRepository->getConfirmedMealIdeas()]);
    }

    public function approveVolunteer(Request $request){
        $this->validate($request, [
            'open_event_id' => 'required',
            'volunteer_id' => 'required',
            'form_status' => 'required'
        ]);
        $event = $this->formRepository->get($request->volunteer_id);
        $this->calendarService->create(CONFIRMED_CALENDAR_ID, $event);
        $this->calendarService->patch(CALENDAR_ID, $event->open_event_id, 'cancelled');   
        $this->formRepository->approve($request->volunteer_id, $result->id);
        // Send Approval email
        return redirect('/admin');

    }
    public function denyVolunteer(equest $request){
        $this->validate($request, [
            'open_event_id' => 'required',
            'volunteer_id' => 'required',
            'form_status' => 'required'
        ]);
        $this->formRepository->deny($request->volunteer_id);
        return redirect('/admin');
    }

    public function cancelConfirmedEvent(Request $request){
        $this->calendarService->patch(CONFIRMED_CALENDAR_ID, $request->confirmed_event_id, 'cancelled');
        if($this->formRepository->getOpenEventCount($request->open_event_id) == 1) {
            $this->calendarRepository->patch(CALENDAR_ID, $request->open_event_id, 'confirmed');
        } 
        $this->formRepository->cancelled($request->volunteer_id);
        flash( "Volunteer Event Cancelled Succesfully")->success();
        return redirect('/admin/form/all');
    }
    
    public function updateVolunteerForm(){
        strtolower($request['paper_goods'][0]) == 'y' ? $request->merge(['paper_goods' => 1]) : $request->merge(['paper_goods' => 0]);
        $this->validate($request, [
            'organization_name' => 'required',
            'phone' => 'required',
            'email' => 'required',
            'meal_description' => 'required',
            'open_event_id' => 'required',
            'event_date_time' => 'required',
            'paper_goods' => 'required',
            'volunteer_id' => 'required',
            'confirmed_event_id' => 'required'
        ]);

        $this->calendarRepository->updateVolunteerEvent($request);
        $this->formRepository->update($request->all(), 1);
        flash( "Form Updated Succesfully")->success();
        return redirect('/admin/form/all');
    }





    public function reviewMealIdea(Request $request)
    {
        $request['display'] = $request['display'] == "on" ? true : false;
        $request['ingredients'] = json_encode(array_map(function ($val) {
            return trim($val);
        }, explode(",", $request->ingredients)));
        
        $this->validate($request, [
            'id' => 'required',
            'description' => 'required',
            'ingredients' => 'required',
            'new_status' => 'required'
        ]);

        // Check the new status on the request
        if ($request->new_status == 1) {
            // Update the meal idea with any changes and approve
            $this->mealRepository->approve($request->id, $request);
        } // Denied
        else if ($request->new_status == 2) {
            $this->mealRepository->deny($request->id);
        }
        return redirect()->back();
    }


    public function sendApprovedEmail($form)
    {
        $messages = $this->messagesRepository->allContent();
        $admin_emails = explode(',', INTERFAITH_ADMINS);

        // To Interfaith
        foreach($admin_emails as $email){
            Mail::to($email)
                ->send(new AdminApproveEmail($form, $messages));
        }

        // To the Volunteer
        Mail::to($form["email"])
            ->send(new VolunteerApprovedEmail($form, $messages));

        return redirect('/');
    }

    public function getMessages(Request $request)
    {
        // get all message objects
        $messages = $this->messagesRepository->all();

        forEach($messages as $message) {
            // change underscores to user-friendly format
            $message->type_str = Utils::transformUnderscoreText($message->type);

            // display "never" if the message hasn't been updated
            if($message->updated_at == null) {
                $message->updated_str = "Never";
            }
            else {
                $message->updated_str = date('m-d-Y', strtotime($message->updated_at));
            }

        }
        return view('messages', ['messages' => $messages]);
    }

    public function updateMessage(Request $request)
    {
        // validate inputs
        $this->validate($request, [
            'id' => 'required',
            'message-content' => 'required',
        ]);
        
        // get the user id and save the message
        if(Auth::check()) {
            $userId = Auth::id();
            $input = [
                'id' => $request['id'],
                'content' => $request['message-content'],
                'user_id' => $userId
            ];
            try {
                $this->messagesRepository->update($input);
                flash( "Your message was saved successfully!")->success();
            }
            catch(Exception $e) {
                flash("There was a problem saving your message. Please try again later.")->error();
            }
        }
        return redirect('admin/settings/change-messages');
    }


}
