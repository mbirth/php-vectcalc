Vector calculation tool
=======================

This is a PHP script that creates sort of a calculation environment for working with
vectors. Not only will it calculate various operations with vectors, it will also plot
them, calculate angles between different vectors, intersection points, etc.

Online Demo
-----------

https://vect.birth-online.de


Syntax
------

    set P1=[1,1,1]

Sets `P1` to the vector 1, 1, 1 (x, y, z).

    set P2=[2,0]

Sets `P2` to the vector 2, 0(, 0).

    plotL P1;P2

Plots a line between vectors `P1` and `P2`.

    plotdel all

Removes all plots from screen.

    unset P2

Deletes variable `P2`.

More commands are available from the onscreen help box.
