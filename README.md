# Coding challenge
## Task

> Please write a class responsible for sending an email to a Slack channel.
> 
> Requirements:
>  * use Symfony framework
>  * create unit tests
>  * use the best coding standards recommended by the Symfony documentation and PHP community
>  * create a pseudo pull request where you explain the rationale for the choices and trade-offs you made 
>
> Please don't spend more than 1-2 hours on this task.

## Solution 

### Logical Assumptions

As we talk about short task, following logical assumptions were made:
1. Task will be solved with Symfony Notifier not Messenger  
2. This also means that messaging bus would be an exaggeration.

### Technical assumptions  

1. Task has been part of a developed system, so no initial actions (like symfony installation, entry configuration, php, nginx|apache, etc.) were necessary. 
2. Following modules were installed:
    > ```composer require symfony/slack-notifier```
3. Slack bot token was generated, obtained and put as ENV variable.
4. Email channel has been established and configured.

### Solutions

1. Having a look at the symfony/slack-notifier dependencies (and corresponding classes), first and most simple way to sending email messages to Slack channel is to manage them through notification priority. This means, all we need is to have a `notifier.channel_policy` configured. So any notification with priority `URGENT` would be transported to Slack channel without any additional data manipulation, while all other priorities would force to send pure email notification. 
   ```yaml
   # config/packages/notifier.yaml
   framework:
       notifier:
               #...
           channel_policy:
               urgent: ['chat/slack']
               high: ['email']
               medium: ['email']
               low: ['email']
   ```
2. `src/Service/Notification/EmailToSlackNotificationHandler.php` class has been tailored according to request. 
   * I wasn't sure how email message would be created in the hypothetical system so any kind of Email message should be transpiled into implementation of `Symfony\Component\Notifier\Message\MessageInterface` class, 
   * namming follows what I was able to notice as potential convention in `symfony/notifier` package, 

### Tests 
1. Handler class and Test class were tested with PhpUnit and PhpStan (level 7) as a part of real Symfony application.  
2. Implementation of Slack notifications online was tested manually in the same env. 

## Additional Remarks

All necessary configuration files are put into pseudo commit as well.    
