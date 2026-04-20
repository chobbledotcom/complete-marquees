<?php

    // Only process POST reqeusts.
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get the form fields and remove whitespace.
        $name = strip_tags(trim($_POST["name"]));
		  $name = str_replace(array("\r","\n"),array(" "," "),$name);
        $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
        $phone = trim($_POST["phone"]);
        $location = trim($_POST["location"]);
        $postcode = trim($_POST["postcode"]);
        $date = trim($_POST["date"]);
        $product = trim($_POST["product"]);
        $message = trim($_POST["message"]);

        // Check that data was sent to the mailer.
        if ( empty($name) OR empty($phone) OR empty($location) OR empty($postcode) OR empty($date) OR empty($message) OR !filter_var($email, FILTER_VALIDATE_EMAIL ) ) {
            // Set a 400 (bad request) response code and exit.
            http_response_code(400);
            echo "Oops! There was a problem with your submission. Please complete the form and try again.";
            exit;
        }

        // Set the recipient email address.
        $recipient = "completemarquee@gmail.com";
        //$recipient = "nick@midasmedia.co.uk";

        // Set the email subject.
        $subject = "New Complete Marquees enquiry from $name";

        //Email content.
        $email_content .= "<html><body><table width='100%' style='width:100%; border-collapse: collapse;'><tr><td style='border-bottom:1px solid #ebebeb;'><h2>Complete Marquees Enquiry</h2></td></tr>";
        $email_content .= "<tr><td style='padding:8px; background:#f5f5f5; border-bottom:1px solid #ebebeb;'><p><b>Name: </b> ".$name."</p></td></tr>";
        $email_content .= "<tr><td style='padding:8px; border-bottom:1px solid #ebebeb;'><p><b>Email: </b> ".$email."</p></td></tr>";
        $email_content .= "<tr><td style='padding:8px; background:#f5f5f5; border-bottom:1px solid #ebebeb;'><p><b>Phone: </b> ".$phone."</p></td></tr>";
        $email_content .= "<tr><td style='padding:8px; border-bottom:1px solid #ebebeb;'><p><b>Venue location: </b> ".$location."</p></td></tr>";
        $email_content .= "<tr><td style='padding:8px; background:#f5f5f5; border-bottom:1px solid #ebebeb;'><p><b>Venue postcode: </b> ".$postcode."</p></td></tr>";
        $email_content .= "<tr><td style='padding:8px; border-bottom:1px solid #ebebeb;'><p><b>Date of event: </b> ".$date."</p></td></tr>";
        if (!empty($product)) {
            $email_content .= "<tr><td style='padding:8px; background:#f5f5f5; border-bottom:1px solid #ebebeb;'><p><b>Interested in: </b> ".$product."</p></td></tr>";
        }
        $email_content .= "<tr><td style='padding:8px; border-bottom:1px solid #ebebeb;'><p><b>Message: </b> ".$message."</td></tr></table></body></html>";
 
        // Build the email headers.
        $email_headers = "From: $name <$email>\r\n";
        $email_headers .= "Reply-To: ".$email."\r\n";
        $email_headers .= "MIME-Version: 1.0\r\n";
        $email_headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

        // Send the email.
        if (mail($recipient, $subject, $email_content, $email_headers)) {
            // Set a 200 (okay) response code.
            http_response_code(200);
            //echo "Thank You! Your message has been sent to Complete Marquees, we will be in touch soon."; 
						header('Location: http://completemarquees.co.uk/success/');
            
        } else {
            // Set a 500 (internal server error) response code.
            http_response_code(500);
            //echo "Oops! Something went wrong and we couldn't send your message.";
						header('Location: http://completemarquees.co.uk/try-again/');
        }

    } else {
        // Not a POST request, set a 403 (forbidden) response code.
        http_response_code(403);
        echo "There was a problem with your submission, please try again..";
    }

?>