# Import Completion #

This plugin gives you the ability to directly import grades, activity and course completions directly
into the database. There are two types of files accepted: course and module completions. Below are the
expected headers for the module completions:

userid(or profilefield to use instead), moduleid, grade, dategraded (in format chosen), timecompleted

Below are the expected headers for the course completions:

userid(or profilefield to use instead), course, timecompleted, timestarted

The module completion import is responsible for adding the grades as well. It is recommended to add the course 
completion before the module completion to avoid a course completion being added for the wrong date.

Note: this plugin will not add the activity level specific tracking information. 

## License ##

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.
