# Email Notifications
Email notifications are an important part of any form, to both notify the user their submission has been received, and to notfy admin's of their submission so they can action.

Each notification is form-specific, and you can create as many notifications as required. There are a number of fields for a notification:

- Enabled - Whether the notification should be sent.
- Name - An internal name to call this notification in the control panel.
- Recipients - A list of email addresses that this notification should go to.
- Subject - The subject line for the email.
- Email Content - The full body of the email. See [Email Content]().
- From Name - Set the name attribute that the email is shown to have come from.
- From Email - The senders email address.
- Reply-To Email - The reply-to email address for the email.
- CC - The cc email address for the email.
- BCC - The bcc email address for the email.
- Attach File Uploads - Whether to attach any user-uploaded files to the email.
- Email Template - To select a custom [Email Template]().

## Email Content
The email content field is a rich-text field providing basic formatting functionality. Due to the complexities of email rendering, this is kept simple on purpose. If you wish to build custom templates for your emails, read the [Email Templates]() docs.

One feature of this field is the variable select field. This allows you to pull in dynamic content from Craft, or from the submission this email notification is made on. Commonly, you'll want to use the "All Fields" option to generate a full list of field's and their content, producing similar content to:

```
**First Name:**
Peter

**Last Name:**
Sherman

**Email**
psherman@wallaby.com

**Message**
Just wanted to say, I love the new website!
```

But other variables exists, such as (but not restricted to):

- User Information (if the submission is made by a logged-in user)
    - User ID
    - User Email
    - Username
    - User Full Name
    - User IP Address
- Time/Date
- System and site settings

Along with all available fields used in your form.

## Sending Emails
Formie uses Craft's Queue system to send out email notifications. The reason for this is performance. Often sending of emails can be slow, particularly for SMTP relays. This slowness can be a detrimental experience for your users, as they wait for the page to load after submitting a form. This is compounded by if multiple email notifications need to be sent out for a form submission. This can lead to user frustration, or even worse - navigating away and not bothering filling in your form.

For this reason, we highly recommend you read the following guides on ensuring your Craft install is [properly configured for email delivery](https://craftcms.com/guides/why-doesnt-craft-send-emails#setting-up-email), and your [runQueueAutomatically](https://docs.craftcms.com/v3/config/config-settings.html#runqueueautomatically) config setting.

For further information about the best-practices with queues, we recommend reading [Robust queue job handling in Craft CMS](https://nystudio107.com/blog/robust-queue-job-handling-in-craft-cms).