Description of Twitter bootstrap import into Moodle

Twitter bootstrap
-----------------

Sass:
This theme uses the original unmodified version 4.0.0 Twitter bootstrap sass files.
The bootstrap repository is available on:

https://github.com/twitter/bootstrap.git

To update to the latest release of twitter bootstrap:
* re-apply /* rtl:begin:ignore */ on the top of _popover.scss before .popover rule and /* rtl:end:ignore */ before
  .popover-arrow::after rule. See MDL-56763 commit (1a4faf9b).
* remove all files from scss/bootstrap,
* download the new scss files and store them in scss/bootstrap
* update ./thirdpartylibs.xml

Javascript:

This theme uses the transpiled javascript from bootstrap4 as amd modules.

To update the javascript files:
Checkout the latest branch of bootstrap to a folder, Run the follwing inside the cloned Bootstrap repository:

```
$ npm install @babel/cli@7.0.0-beta.37 @babel/preset-env@7.0.0-beta.37 babel-plugin-transform-es2015-modules-amd @babel/plugin-proposal-object-rest-spread
$ ./node_modules/@babel/cli/bin/babel.js --presets @babel/preset-env --plugins transform-es2015-modules-amd,@babel/plugin-proposal-object-rest-spread -d /path/to/your/moodle/dirroot/theme/reboost/amd/src
```
(thanks to Joby Harding)

Copy the transpiled files from out/ into the amd/src/ folder for the theme.
Run grunt to re-compile the JS files.

Popper JS

Bootstrap 4 has a peer dependency on [Popper](https://popper.js.org). Note that while popper is included in core `admin/tool/usertours/amd/src/popper.js` it is an older version.
```
$ git clone https://github.com/FezVrasta/popper.js.git
$ git checkout 1.12.9 # or whatever the latest release tag is
```
Copy `dist/popper.js` to `path/to/your/moodle/dirroot/theme/reboost/amd/src` then run the amd build task.

Finally update the theme `thirdpartylibs.xml` to reflect the version of Popper.