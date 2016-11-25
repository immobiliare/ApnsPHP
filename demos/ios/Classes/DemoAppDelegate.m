//
//  DemoAppDelegate.m
//  Demo
//
// LICENSE
//
// This source file is subject to the new BSD license that is bundled
// with this package in the file LICENSE.txt.
// It is also available through the world-wide-web at this URL:
// http://code.google.com/p/apns-php/wiki/License
// If you did not receive a copy of the license and are unable to
// obtain it through the world-wide-web, please send an email
// to aldo.armiento@gmail.com so we can send you a copy immediately.
//
// @author (C) 2010 Aldo Armiento (aldo.armiento@gmail.com)
// @version $Id$
//

#import "DemoAppDelegate.h"

@implementation DemoAppDelegate

@synthesize window, textView;

#pragma mark -
#pragma mark Application lifecycle

- (BOOL)application:(UIApplication *)application didFinishLaunchingWithOptions:(NSDictionary *)launchOptions {    
  [window makeKeyAndVisible];

#if !TARGET_IPHONE_SIMULATOR
  [application registerForRemoteNotificationTypes: 
   UIRemoteNotificationTypeAlert | UIRemoteNotificationTypeBadge | UIRemoteNotificationTypeSound];
#endif
  
  application.applicationIconBadgeNumber = 0;
  self.textView.text = [launchOptions description];
  
  return YES;
}

- (void)application:(UIApplication *)application didReceiveRemoteNotification:(NSDictionary *)userInfo {
  application.applicationIconBadgeNumber = 0;
  self.textView.text = [userInfo description];
  
  // We can determine whether an application is launched as a result of the user tapping the action
  // button or whether the notification was delivered to the already-running application by examining
  // the application state.
  
  if (application.applicationState == UIApplicationStateActive) {
    // Nothing to do if applicationState is Inactive, the iOS already displayed an alert view.
    UIAlertView *alertView = [[UIAlertView alloc] initWithTitle:@"Did receive a Remote Notification"
                                                        message:[NSString stringWithFormat:@"The application received this remote notification while it was running:\n%@",
                                                                 [[userInfo objectForKey:@"aps"] objectForKey:@"alert"]]
                                                       delegate:self
                                              cancelButtonTitle:@"OK"
                                              otherButtonTitles:nil];
    [alertView show];
    [alertView release];
  }
}

- (void)applicationDidBecomeActive:(UIApplication *)application {
  application.applicationIconBadgeNumber = 0;
}

#pragma mark -
#pragma mark Remote notifications

- (void)application:(UIApplication *)application didRegisterForRemoteNotificationsWithDeviceToken:(NSData *)deviceToken {
  // You can send here, for example, an asynchronous HTTP request to your web-server to store this deviceToken remotely.
  NSLog(@"Did register for remote notifications: %@", deviceToken);
}

- (void)application:(UIApplication *)application didFailToRegisterForRemoteNotificationsWithError:(NSError *)error {
  NSLog(@"Fail to register for remote notifications: %@", error);
}

#pragma mark -
#pragma mark Memory management

- (void)dealloc {
    [window release];
    [super dealloc];
}

@end
