[![Build Status](https://travis-ci.org/uppes/processor.svg?branch=master)](https://travis-ci.org/uppes/processor)[![Build status](https://ci.appveyor.com/api/projects/status/5ns559880b4nsi3j/branch/master?svg=true)](https://ci.appveyor.com/project/techno-express/processor/branch/master)[![codecov](https://codecov.io/gh/uppes/processor/branch/master/graph/badge.svg)](https://codecov.io/gh/uppes/processor)[![Codacy Badge](https://api.codacy.com/project/badge/Grade/97cfd5c519bf4dc489eda97d7b61c00b)](https://www.codacy.com/app/techno-express/processor?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=uppes/processor&amp;utm_campaign=Badge_Grade)

Processor
=====

An simply process control wrapper API for [symfony/process](https://github.com/symfony/process) to execute commands in sub-processes.

It's an alternative to pcntl-extension, when not installed. This is part of our [uppes/eventloop](https://github.com/uppes/eventloop) package for asynchronous PHP programming.

The library is to provide an easy to use API to control/manage sub processes for windows OS, and other systems, without any additional software installed.
