<?php

namespace App\Controllers;

use Titan\Http\Request;
use Titan\Http\Response;

/**
 * Home Controller
 *
 * Handles the main pages of the application.
 */
class HomeController extends Controller
{
    /**
     * Show the welcome page.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return $this->view('welcome', [
            'title' => 'Welcome to Titan Framework',
            'description' => 'A powerful, secure, and developer-friendly PHP framework',
        ]);
    }

    /**
     * Show the hello page.
     *
     * @param Request $request
     * @return Response
     */
    public function hello(Request $request): Response
    {
        $name = $request->query('name', 'World');

        return $this->view('hello', [
            'title' => 'Hello Page',
            'name' => $name,
        ]);
    }

    /**
     * Show the about page.
     *
     * @param Request $request
     * @return Response
     */
    public function about(Request $request): Response
    {
        return $this->view('about', [
            'title' => 'About Titan Framework',
            'features' => [
                'Modern PHP 8.2+ framework',
                'PSR compliant',
                'Powerful routing system',
                'Advanced dependency injection',
                'High performance',
                'Elegant syntax',
                'Robust security features',
            ],
        ]);
    }

    /**
     * Show the contact page.
     *
     * @param Request $request
     * @return Response
     */
    public function contact(Request $request): Response
    {
        return $this->view('contact', [
            'title' => 'Contact Us',
        ]);
    }

    /**
     * Process the contact form.
     *
     * @param Request $request
     * @return Response
     */
    public function submitContact(Request $request): Response
    {
        // Validate the request
        $name = $request->input('name');
        $email = $request->input('email');
        $message = $request->input('message');

        $errors = [];

        if (empty($name)) {
            $errors['name'] = 'Name is required';
        }

        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email is invalid';
        }

        if (empty($message)) {
            $errors['message'] = 'Message is required';
        }

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        // Process the contact form (in a real app, this would save to a database or send email)

        // Redirect back with success message
        return $this->redirect('/contact/success');
    }
}
