This extension is an addition to Shoppimon monitoring application, and allows user to access his Shoppimon monitoring dashboard inside Magento Admin panel, as well as enhances data collection by Shoppimon.

How do I install the Monitoring by Shoppimon extension in my Magento admin panel?

In order to install the Monitoring by Shoppimon dashboard, you must first be a registered Shoppimon user. If you are not yet registered, just go to https://www.shoppimon.com/#sign-up to sign up. It will only take a moment.
Once you have a registered account, please follow these steps:
1. Log in to your Shoppimon Account
2. Go to the API Tab in your Shoppimon Account Settings
3. Click on the Generate API Key button
4. Copy the API Keys (both Client ID and Client Secret)
5. Log in to your Magento Admin Panel
6. From your Magento Admin Panel Menu select System and Configuration.
7. Find the Shoppimon tab and paste the API keys
8. Once you’ve entered the API keys. Go to System and Cache Management, then press the ‘Flush Magento Cache’ button
9. Log out from your Magento Admin Panel and log back in
10. Shoppimon should now appear in your admin panel menu
11. Hover over Shoppimon and select Business Monitoring to see your new dashboard

If you’d like any additional information, or if you have any questions, feel free to contact us at support@shoppimon.com.

=================================================
Do I have to add the dashboard to my Magento Admin, or is it possible to use the extension only to pull additional real-time data and insights into my Shoppimon account?

Yes, it is absolutely possible. If you wish to use the extension only to pull additional real-time data into your Shoppimon account, without adding another dashboard to your Magento admin, just follow these steps:
1. Go to your Magento Database
2. In the core_configure_data table, identify the field where the "path" column is equal to ‘shoppimon/settings/show_dashboard’
3. You can then change the "value" of that field 1 to 0, where 1 shows the dashboard in your Magento admin, and 0 means data will be pulled to your Shoppimon account, without adding the dashboard

If you’d like any additional information, or if you have any questions, feel free to contact us at support@shoppimon.com.
