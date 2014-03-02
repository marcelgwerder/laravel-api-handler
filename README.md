##Laravel API Handler##

This is going to be a helper package which provides functionality for url parsing and response handling on a Laravel REST-API.

###URL Parsing###

####Filtering####

#####Suffixes#####
Suffix        | Operator      | Meaning
------------- | ------------- | -------------
-lk           | LIKE          | Same as the SQL `LIKE` opearator
-not-lk       | NOT LIKE      | Same as the SQL `NOT LIKE` operator
-min          | >=            | Greater than or equal to
-max          | <=            | Smaller than or equal to
-st           | <             | Smaller than
-gt           | >             | Greater than
-not          | !=            | Not equal to

####Sorting####

####Full Text Search####

####Limit The Result Set###

####Include Related Models####

####Include Meta Information####

###Response Handling###

####Error Response####

####Success Response####
