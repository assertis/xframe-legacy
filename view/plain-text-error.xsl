<?xml version="1.0"?>
<stylesheet version="1.0" xmlns="http://www.w3.org/1999/XSL/Transform">

<output method="text"/>

<variable name='newline'><text>
</text></variable>

<template match="/">
<if test="count(/root/errors/error)!=0 or count(/root/exceptions/exception)!=0">

Error generating page
<call-template name="display-errors" />
</if>
</template>

<template name="display-errors">
<if test="count(/root/errors/error)!=0">
The following errors occured:

<for-each select="/root/errors/error">

<value-of select="./@type" />: <value-of select="./message"  />
Backtrace:

<for-each select="./backtrace/step">[<value-of select="./@number" />] line <value-of select="./@line" /> of <value-of select="./@file" /> called <value-of select="./@class" /><if test="./@class!=''">-></if><value-of select="./@function" />()
</for-each>

Location: <value-of select="./location"  />
Connection from: <value-of select="./ipaddress"  />
GET values: 
<for-each select="./getVariables/variable">
    <text>&#9;</text><value-of select="./@key"  /><if test="string-length(./@key)&lt; 8"><text>&#9;</text></if><if test="string-length(./@key)&lt; 16"><text>&#9;</text></if><text>&#9;</text>: <value-of select="concat(./@value, $newline)"  />
</for-each>
POST values: 
<for-each select="./postVariables/variable">
    <text>&#9;</text><value-of select="./@key"  /><if test="string-length(./@key)&lt; 8"><text>&#9;</text></if><if test="string-length(./@key)&lt; 16"><text>&#9;</text></if><text>&#9;</text>: <value-of select="concat(./@value, $newline)"  />
</for-each>

</for-each>
    </if>
    <if test="count(/root/exceptions/exception)!=0">
The following exceptions occured:

<for-each select="/root/exceptions/exception">
<if test="./@uncaught='true'">Uncaught </if>Exception: <value-of select="./message"  />
Code: <value-of select="./code"  />
Backtrace:

<for-each select="./backtrace/step">[<value-of select="./@number" />] line <value-of select="./@line" /> of <value-of select="./@file" /> called <value-of select="./@class" /> <if test="./@class!=''">-></if><value-of select="./@function" />()
</for-each>

Location: <value-of select="./location"  />
Connection from: <value-of select="./ipaddress"  />
GET values: 
<for-each select="./getVariables/variable">
    <text>&#9;</text><value-of select="./@key"  /><if test="string-length(./@key)&lt; 8"><text>&#9;</text></if><if test="string-length(./@key)&lt; 16"><text>&#9;</text></if><text>&#9;</text>: <value-of select="concat(./@value, $newline)"  />
</for-each>
POST values: 
<for-each select="./postVariables/variable">
    <text>&#9;</text><value-of select="./@key"  /><if test="string-length(./@key)&lt; 8"><text>&#9;</text></if><if test="string-length(./@key)&lt; 16"><text>&#9;</text></if><text>&#9;</text>: <value-of select="concat(./@value, $newline)"  />
</for-each>

</for-each>
    </if>
</template>
</stylesheet>
