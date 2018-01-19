<?php

namespace CSUNMetaLab\Support\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

use Illuminate\Support\Facades\Mail;

use CSUNMetaLab\Support\Exceptions\InvalidFeedbackSenderException;
use CSUNMetaLab\Support\Exceptions\FeedbackModelNotFoundException;

use CSUNMetaLab\Support\Http\Requests\FeedbackFormRequest;

class FeedbackController extends BaseController
{
	/**
	 * Displays the form to submit a feedback message.
	 *
	 * @return View
	 */
	public function create() {
		return view("support::forms.feedback");
	}

	/**
	 * Accepts the feedback submission and redirects back upon success.
	 *
	 * @return RedirectResponse
	 *
	 * @throws InvalidFeedbackSenderException
	 * @throws FeedbackModelNotFoundException
	 */
	public function store(FeedbackFormRequest $request) {
		// ensure we have a valid sender address before we go any further
		if(!config('support.senders.feedback.address')) {
			$msg = trans('support.errors.feedback.invalid_sender');
			logger()->error($msg);
			throw new InvalidFeedbackSenderException($msg);
		}

		$content = $request->input('content');

		// retrieve the name and email attributes dynamically
		$idAttr = config('support.submitter.id', 'id');
		$nameAttr = config('support.submitter.name', 'name');
		$emailAttr = config('support.submitter.email', 'email');
		$user_id = auth()->user()->$idAttr;
		$name = auth()->user()->$nameAttr;
		$email = auth()->user()->$emailAttr;

		// determine what to report as the application name
		$appName = config('app.name', 'Laravel');
		if(config('support.allow_application_name_override')) {
			if($request->input('application_name', null)) {
				$appName = $request->input('application_name');
			}
		}

		if(class_exists('Illuminate\Mail\Mailable')) {
			// use an instance of a custom Mailable instance that is also
			// queueable
		}
		else
		{
			// send the email using the Mail facade and the queue() method
		}

		// write the record to the database if database support is enabled
		if(config('support.database.enabled')) {
			$model = config('support.database.models.feedback');
			if(class_exists($model)) {
				$model::create([
					'application_name' => $appName,
					'user_id' => $user_id,
					'content' => $content,
				]);
			}
			else
			{
				// model could not be resolved, so log out the error and then
				// throw a catchable exception
				$msg = trans('support.errors.feedback.model_not_found', [
					'model' => $model
				]);
				logger()->error($msg);
				throw new FeedbackModelNotFoundException($msg);
			}
		}

		// there was some kind of success, so re-direct back to the form
		return redirect()->back()->with('message',
			trans('support.success.feedback'));
	}
}