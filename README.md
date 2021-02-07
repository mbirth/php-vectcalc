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


Example
-------

Copy this into the input box.

    set P1=[1,-1,0]
    set P2=P1+[1,0,0]
    set P3=P1+[1,-1,0]
    set P4=P1-[0,1,0]
    set P5=P1+[0,0,1]
    set P6=P2+[0,0,1]
    set P7=P3+[0,0,1]
    set P8=P4+[0,0,1]
    plotdel all
    plotL P1;P2
    plotL P1;P4
    plotL P2;P3
    plotL P3;P4
    plotL P1;P5
    plotL P2;P6
    plotL P3;P7
    plotL P5;P6
    plotL P6;P7
