[![Build Status](https://travis-ci.org/symplely/processor.svg?branch=master)](https://travis-ci.org/symplely/processor)[![Build status](https://ci.appveyor.com/api/projects/status/5ns559880b4nsi3j/branch/master?svg=true)](https://ci.appveyor.com/project/techno-express/processor/branch/master)[![codecov](https://codecov.io/gh/symplely/processor/branch/master/graph/badge.svg)](https://codecov.io/gh/symplely/processor)[![Codacy Badge](https://api.codacy.com/project/badge/Grade/97cfd5c519bf4dc489eda97d7b61c00b)](https://www.codacy.com/app/techno-express/processor?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=symplely/processor&amp;utm_campaign=Badge_Grade)

Processor
=====

An simply __process control__ wrapper API for [symfony/process](https://github.com/symfony/process) to execute manage *sub-processes*.

It's an alternative to pcntl-extension, when not installed. This is part of our [symplely/coroutine](https://github.com/symplely/coroutine) package for asynchronous PHP programming.

The library is to provide an easy to use API to control/manage sub processes for windows OS, and other systems, without any additional software installed.
