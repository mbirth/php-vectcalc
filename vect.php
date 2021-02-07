<?php

ini_set('display_errors', 'stderr');
set_error_handler('e');

define('CRLF', "\r\n");

session_name('VECTCALC');
session_start();

if (!isset($_SESSION['arystore'])) $_SESSION['arystore'] = array('Ans'=>'0');
if (!isset($_SESSION['plot'])) $_SESSION['plot'] = array();
if (!isset($_SESSION['lastcmd'])) $_SESSION['lastcmd'] = '';

if (isset($_GET['plotme'])) {
  $colors = array('f00', '080', '00f', '880', '808', '088', '008', '800', '888');
  $maxx = 360;
  $maxy = 360;
  $im = imagecreatetruecolor($maxx, $maxy);
  $bg = allochex($im, 'fff');
  $fg = allochex($im, '000');
  imagefilledrectangle($im, 0, 0, $maxx-1, $maxy-1, $bg);
  imagerectangle($im, 0, 0, $maxx-1, $maxy-1, $fg);
  imagealphablending($im, true);
  imageantialias($im, true);
  // TODO: find highest dimensions or use default (0 .. 5)
  $dim = array(999, -999, 999, -999);
  if (!isset($_SESSION['plotdim'])) {
    // TODO: find highest dimensions or use default (0 .. 5)
    foreach ($_SESSION['plot'] as $p) {
      list ($pt, $pp) = explode(':', $p, 2);
      switch ($pt) {
        case 'L':
          list ($p1, $p2) = explode(';', $pp, 2);
          $a = solve($p1);
          $b = solve($p2);
          maxdim($dim, getxy($a));
          maxdim($dim, getxy($b));
          break;

        case 'G':
          list ($gp, $gv) = explode(';', $pp, 2);
          $a = solve($gp);
          $b = solve($gp . '+' . $gv);
          maxdim($dim, getxy($a));
          maxdim($dim, getxy($b));
          break;

        case 'E':
          list ($ep, $ev1, $ev2) = explode(';', $pp, 3);
          $a = solve($ep);
          $b = solve($ep . '+' . $ev1);
          $c = solve($ep . '+' . $ev2);
          $pts = array();
          maxdim($dim, getxy($a));
          maxdim($dim, getxy($b));
          maxdim($dim, getxy($c));
          break;
      }
    }
    $dim[0] -= 1;
    $dim[1] += 1;
    $dim[2] -= 1;
    $dim[3] += 1;
  } else {
    $dim = explode(';', $_SESSION['plotdim'], 4);
  }
  $sx = $maxx/(abs($dim[0])+abs($dim[1]));   // step 1 LE in x is $sx pixels
  $sy = $maxy/(abs($dim[2])+abs($dim[3]));   // step 1 LE in y is $sy pixels
  $cx = $sx*abs($dim[0]);
  $cy = $sy*abs($dim[3]);
  imagestring($im, 1, 2, 1, 'Plot [' . $dim[0] . '..' . $dim[1] . '], [' . $dim[2] . '..' . $dim[3] . ']', $fg);
  $mx = $dim[1];
  $my = $dim[3];
  // x-axis and sub+labels
  imageline($im, 0, $cy, $maxx, $cy, $fg);
  $stepsx = getsteps(abs($dim[0])+abs($dim[1]));
  for ($i=$dim[0];$i<=$dim[1];$i+=$stepsx) {
    imageline($im, $cx+$i*$sx, $cy-2, $cx+$i*$sx, $cy+2, $fg);
    imagestring($im, 1, $cx+$i*$sx-(imagefontwidth(1)*strlen($i)/2), $cy+4, $i, $fg);
  }
  // y-axis and sub+labels
  imageline($im, $cx, 0, $cx, $maxy, $fg);
  $stepsy = getsteps(abs($dim[2])+abs($dim[3]));
  for ($i=$dim[2];$i<=$dim[3];$i+=$stepsy) {
    imageline($im, $cx-2, $cy-$i*$sy, $cx+2, $cy-$i*$sy, $fg);
    imagestring($im, 1, $cx+4, $cy-$i*$sy-imagefontheight(1)/2, $i, $fg);
  }
  // z-axis and sub+labels
  $zfact = $mx+$my;
  imageline($im, $cx-floor($zfact*$sx/2), $cy+floor($zfact*$sy/2), $cx+floor($zfact*$sx/2), $cy-floor($zfact*$sy/2), $fg);
  $stepsz = getsteps((abs($dim[0])+abs($dim[1])+abs($dim[2])+abs($dim[3])));
  for ($i=-$mx*10;$i<=$mx*10;$i+=$stepsz) {
    imageline($im, $cx+floor($i*$sx/2)-2, $cy-floor($i*$sy/2)-2, $cx+floor($i*$sx/2)+2, $cy-floor($i*$sy/2)+2, $fg);
    imagestring($im, 1, $cx+floor($i*$sx/2)+imagefontwidth(1)*strlen($i)/2, $cy-floor($i*$sy/2)+imagefontheight(1)/2, $i, $fg);
  }

  asort($_SESSION['plot']);

  foreach ($_SESSION['plot'] as $p) {
    list ($pt, $pp) = explode(':', $p, 2);
    $cc = allochex($im, current($colors), 32);
    switch ($pt) {
      case 'L':
        list ($p1, $p2) = explode(';', $pp, 2);
        $a = solve($p1);
        $b = solve($p2);
        list ($ax, $ay) = getxy($a);
        list ($bx, $by) = getxy($b);
        d('Plot L: ' . $a . ' to ' . $b);
        imageline($im, $cx+$ax*$sx, $cy-$ay*$sy, $cx+$bx*$sx, $cy-$by*$sy, $cc);
        break;
      case 'G':
        list ($gp, $gv) = explode(';', $pp, 2);
        $a = solve($gp . '-100*' . $gv);
        list ($ax, $ay) = getxy($a);
        $b = solve($gp . '+100*' . $gv);
        list ($bx, $by) = getxy($b);
        imageline($im, $cx+$ax*$sx, $cy-$ay*$sy, $cx+$bx*$sx, $cy-$by*$sy, $cc);
        break;

      case 'E':
        list ($ep, $ev1, $ev2) = explode(';', $pp, 3);
        $a = solve($ep);
        $b = solve($ep . '+' . $ev1);
        $c = solve($ep . '+' . $ev2);
        $pts = array();
        $pts[] = $cx+getxy($a, 'x')*$sx;
        $pts[] = $cy-getxy($a, 'y')*$sy;
        $pts[] = $cx+getxy($b, 'x')*$sx;
        $pts[] = $cy-getxy($b, 'y')*$sy;
        $pts[] = $cx+getxy($c, 'x')*$sx;
        $pts[] = $cy-getxy($c, 'y')*$sy;
        imagefilledpolygon($im, $pts, 3, $cc);
        break;
    }
    next($colors);
  }
  // TODO: make conversion function to convert 3d-coordinates to x-y
  header('Content-Type: image/png');
  header('Content-Disposition: inline; filename="vectplot.png"');
  imagepng($im);
  exit;
}

/**
 * Returns coordinates of a projection of point $v onto a 2D graph
 *
 * @param string $v the vector as <pre>[1,-2,3]</pre>
 * @param string $w (optional) use <i>x</i> or <i>y</i> to get only that single ordinate, omit to get both as array
 * @return array|float
 */
function getxy($v, $w = false) {
  $vs = vsplit($v);
  $res = array();
  $res[0] = $vs[0];
  $res[1] = $vs[1];
  if (count($vs)==3) {
    $res[0] += $vs[2]/2;
    $res[1] += $vs[2]/2;
  }
  if ($w === 'x') return $res[0];
  if ($w === 'y') return $res[1];
  return $res;
}

/**
 * Gets a useful stepping for a given span so that there are not too many sections on screen
 *
 * @param int|float $span
 * @return int|float
 */
function getsteps($span) {
  $a = array(1000, 500, 200, 100, 50, 20, 10, 5, 1, 0.5, 0.2, 0.1);
  foreach ($a as $i) {
    if (($span/$i)>=9) return $i;
  }
  return 1;
}

/**
 * Adjusts the given $dim to contain the given x-y-pair $p
 *
 * @param array $dim
 * @param array $p
 */
function maxdim(&$dim, $p) {
  if ($dim[0]>$p[0]) $dim[0] = $p[0];
  if ($dim[1]<$p[0]) $dim[1] = $p[0];
  if ($dim[2]>$p[1]) $dim[2] = $p[1];
  if ($dim[3]<$p[1]) $dim[3] = $p[1];
}

/**
 * Allocates color $hex (defined as hex-values) with alpha $alpha
 *
 * Example:
 * <code>
 * <?php
 * $white = allochex($im, 'fff');
 * $bluealpha = allochex($im, '0846cd', 32);
 * ?>
 * </code>
 *
 * @param resource $img
 * @param string $hex
 * @param int $alpha
 * @return int
 */
function allochex($img, $hex, $alpha = 0) {
  if (strlen($hex) == 3) {
    $hex = $hex{0}.$hex{0}.$hex{1}.$hex{1}.$hex{2}.$hex{2};
  }
  while (strlen($hex)<6) $hex .= '0';
  $r = hexdec($hex{0} . $hex{1});
  $g = hexdec($hex{2} . $hex{3});
  $b = hexdec($hex{4} . $hex{5});
  return imagecolorallocatealpha($img, $r, $g, $b, $alpha);
}

$out = array();
if (isset($_POST['cmd'])) $cmd = $_POST['cmd'];
else $cmd = $_SESSION['lastcmd'];

/**
 * Outputs debug messages if $_GET['dbg'] is set
 *
 * @param string $txt the message to output
 * @param bool $always set to true to always output the message
 */
function d($txt, $always = false) {
  global $out, $im, $impos;
  if (!isset($impos)) $impos = 1;
  if (isset($_GET['plotme']) && isset($_GET['dbg'])) {
    $dbg = allochex($im, '088', 32);
    imagestring($im, 1, 2, 1+$impos++*(imagefontheight(1)-1), strip_tags($txt), $dbg);
    return;
  }
  if (isset($_GET['plotme'])) return;
  if (isset($_GET['dbg']) || $always) $out[] = $txt;
}

/**
 * Error-handler to output errors and warnings with style.
 *
 * @param int $errno
 * @param string $errstr
 * @param string $errfile
 * @param int $errline
 * @param array $errcontext
 * @see d()
 */
function e($errno, $errstr, $errfile, $errline, $errcontext) {
  $errfile = str_replace(str_replace('/', '\\', dirname($_SERVER['SCRIPT_FILENAME'])).'\\', '', $errfile);
  d('<span class="error">#' . $errno . ': ' . $errstr . ' [' . $errline . '@' . $errfile . ']</span>');
}

/**
 * Multiplies all values of array $a with $m
 *
 * @param array $a
 * @param int|float $m
 * @return array
 */
function mmult($a, $m) {
  foreach ($a as $i=>$v) {
    $a[$i] = $v*$m;
  }
  return $a;
}

/**
 * Subtracts the corresponding item of $a2 from each item of $a1
 *
 * @param array $a1
 * @param array $a2
 * @return array
 */
function msub($a1, $a2) {
  foreach ($a1 as $i=>$v) {
    $a1[$i] = $v-$a2[$i];
  }
  return $a1;
}

/**
 * splits a vector as string (like [1,-2,3]) into its single elements
 * This is the counter-part of {@link vcreate()}
 *
 * @param string $v
 * @return array
 * @see vcreate()
 */
function vsplit($v) {
  $v = preg_replace('/\[(.*)\]/', '$1', $v);
  $va = explode(',', $v);
  array_walk($va, 'trim');
  array_walk($va, 'intval');
  return $va;
}

/**
 * Creates a vector as string (like [1,-2,3]) from an array containing the single elements
 * This is the counter-part of {@link vsplit()}
 *
 * @param array $v
 * @return string
 * @see vsplit()
 */
function vcreate($v) {
  return '[' . $v[0] . ',' . $v[1] . ',' . $v[2] . ']';
}

/**
 * Checks whether the given string is a vector (i.e. contains [ and ])
 *
 * @param string $v
 * @return bool
 */
function isvect($v) {
  if (strlen($v)==0) return false;
  if ($v{0} !== '[') return false;
  if ($v{strlen($v)-1} !== ']') return false;
  return true;
}

/**
 * Subtracts two vectors or 2 scalars
 *
 * @param string|int|float $v1
 * @param string|int|float $v2
 * @return string|int|float
 */
function vsub($v1, $v2) {
  if (!isvect($v1) && !isvect($v2)) return $v1-$v2;
  if (!isvect($v1) || !isvect($v2)) {
    d('WARNING: Subtraction can\'t work with vector and scalar. (' . $v1 . '-' . $v2 . ')', true);
    return false;
  }
  $v1s = vsplit($v1);
  $v2s = vsplit($v2);
  $res = array($v1s[0]-$v2s[0], $v1s[1]-$v2s[1], $v1s[2]-$v2s[2]);
  return vcreate($res);
}

/**
 * Adds two vectors or scalars
 *
 * @param string|int|float $v1
 * @param string|int|float $v2
 * @return string|int|float
 */
function vadd($v1, $v2) {
  if (!isvect($v1) && !isvect($v2)) return $v1+$v2;
  if (!isvect($v1) || !isvect($v2)) {
    d('WARNING: Addition can\'t work with vector and scalar. (' . $v1 . '+' . $v2 . ')', true);
    return false;
  }
  $v1s = vsplit($v1);
  $v2s = vsplit($v2);
  $res = array($v1s[0]+$v2s[0], $v1s[1]+$v2s[1], $v1s[2]+$v2s[2]);
  return vcreate($res);
}

/**
 * Scalar-product of two vectors
 *
 * @param string $v1
 * @param string $v2
 * @return int|float
 */
function vdot($v1, $v2) {
  $v1s = vsplit($v1);
  $v2s = vsplit($v2);
  $ans = 0;
  foreach ($v1s as $i=>$v1v) {
    $ans += $v1s[$i]*$v2s[$i];
  }
  return $ans;
}

/**
 * Cross-product of two vectors
 *
 * @param string $v1
 * @param string $v2
 * @return string
 */
function vcross($v1, $v2) {
  $v1s = vsplit($v1);
  $v2s = vsplit($v2);
  $res = array($v1s[1]*$v2s[2]-$v1s[2]*$v2s[1], $v1s[2]*$v2s[0]-$v1s[0]*$v2s[2], $v1s[0]*$v2s[1]-$v1s[1]*$v2s[0]);
  return vcreate($res);
}

/**
 * Multiplication of two numbers or scalar-product of two vectors or multiplies each item of a vector with a number
 *
 * @param string|int|float $v1
 * @param string|int|float $v2
 * @return string|int|float
 */
function vmult($v1, $v2) {
  if (!isvect($v1) && !isvect($v2)) return $v1*$v2;
  if (isvect($v1) && !isvect($v2)) {
    $v1s = vsplit($v1);
    return vcreate(mmult($v1s, $v2));
  }
  if (!isvect($v1) && isvect($v2)) {
    $v2s = vsplit($v2);
    return vcreate(mmult($v2s, $v1));
  }
  return vdot($v1, $v2);   // default for "*"
}

/**
 * Division of two scalars or a vector and a scalar
 *
 * @param string|int|float $v1
 * @param int|float $v2
 * @return string|int|float
 */
function vdiv($v1, $v2) {
  if (!isvect($v1) && !isvect($v2)) return $v1/$v2;
  if (isvect($v2)) d('WARNING: Division can\'t work with vector as divisor! (' . $v1 . '/' . $v2 . ')', true);
  $v1s = vsplit($v1);
  foreach ($v1s as $i=>$vv) {
    $v1s[$i] = $vv/$v2;
  }
  return vcreate($v1s);
}

/**
 * Absolute value of a scalar or vector
 *
 * @param string|int|float $v
 * @return int|float
 */
function vabs($v) {
  if (!isvect($v)) return abs($v);
  $vs = vsplit($v);
  $vsum = 0;
  foreach ($vs as $v) {
    $vsum += $v*$v;
  }
  return sqrt($vsum);
}

/**
 * Implementation of strpos() which ignores everything in square brackets (vectors)
 *
 * @param string $haystack
 * @param string $needle
 * @param int $start
 * @return int|bool
 */
function vstrpos($haystack, $needle, $start = 0) {
  $invect = ($haystack{0}=='[');
  for ($i=$start;$i<strlen($haystack);$i++) {
    if ($haystack{$i} == '[') $invect = true;
    if ($haystack{$i} == ']') $invect = false;
    if ($invect) continue;
    if (substr($haystack, $i, strlen($needle)) === $needle) return $i;
  }
  return false;
}

/**
 * Implementation of explode() which ignores everything in square brackets (vectors)
 *
 * @param string $sep
 * @param string $hay
 * @param int $limit
 * @return array
 */
function vexplode($sep, $hay, $limit) {
  $res = array();
  $oldidx = 0;
  do {
    $idx = vstrpos($hay, $sep);
    d('Pos of ' . $sep . ' in ' . $hay . ' is ' . $idx);
    if ($idx === false) break;
    $res[] = substr($hay, $oldidx, $idx-$oldidx);
    $oldidx = $idx+strlen($sep);
    $limit--;
  } while ($limit > 1);
  $res[] = substr($hay, $oldidx);
  return $res;
}

/**
 * Implodes a given 2D-array to a matrix for human reading
 *
 * @param array $m
 * @return string
 */
function mimplode($m) {
  $res = array();
  foreach ($m as $mi) {
    $res[] = '[' . implode(',', $mi) . ']';
  }
  return '[' . implode(',', $res) . ']';
}

/**
 * Gaussian elimination to solve linear equations
 *
 * Example:
 * <code>
 * <?php
 * //  2x + y -  z =   8
 * // -3x - y + 2z = -11
 * // -2x + y + 2z =  -3
 * $in = array(
 *   array( 2,  1, -1,   8),
 *   array(-3, -1,  2, -11),
 *   array(-2,  1,  2,  -3),
 * );
 * $out = gauss($in);
 * // $out = array(2, 3, -1)
 * ?>
 * </code>
 *
 * @param array $m input matrix
 * @return array matrix of coefficients
 */
function gauss($m) {
  $c = count($m);
  $u = count($m[0])-1;
  d('(Gaussian elimination: ' . $u . ' unknowns and ' . $c . ' equations)', true);
  d('Input matrix: ' . mimplode($m));

  // BEGIN: BORROWED AND MODIFIED from http://www.phpmath.com/home?op=perm&nid=82
  // Smallest deviation allowed in floating point comparisons.
  $EPSILON = 1e-10;

  // forward elimination
  for ($p=0; $p<$u; $p++) {
    // find pivot row and swap
    $max = $p;
    for ($i=$p+1; $i<$c; $i++) {
      if (abs($m[$i][$p]) > abs($m[$max][$p])) {
        $max = $i;
      }
    }
    $tmp = $m[$p];
    $m[$p] = $m[$max];
    $m[$max] = $tmp;

    if (abs($m[$p][$p]) <= $EPSILON) {
      d('WARNING: Matrix is singular or nearly singular!', true);
      return false;
    }

    // pivot within matrix
    for ($i=$p+1; $i<$u; $i++) {
      $alpha = $m[$i][$p] / $m[$p][$p];
      $m[$p] = mmult($m[$p], $alpha);
      $m[$i] = msub($m[$i], $m[$p]);
    }
  }

  // back substitution
  $res = array_fill(0, $u-1, 0);
  for ($i = $u-1; $i>=0; $i--) {
    $sum = 0.0;
    for ($j = $i+1; $j<$u; $j++) {
      $sum += $m[$i][$j] * $res[$j];
    }
    $res[$i] = ($m[$i][$u] - $sum) / $m[$i][$i];
  }
  // END: BORROWED AND MODIFIED from http://www.phpmath.com/home?op=perm&nid=82
  return $res;
}

/**
 * Solves an equation by splitting it into its single parts, replacing variables and choosing the correct calculations
 *
 * @param string $e
 * @param int $lev (optional)
 * @return string|int|float
 */
function solve($e, $lev = 0) {
  $sep = false;
  if (vstrpos($e, ')')!==false) {
    $i1 = vstrpos($e, '(');
    $i2 = vstrpos($e, ')');
    $ins = substr($e, $i1+1, $i2-$i1-1);
    $left = substr($e, 0, $i1);
    $rght = substr($e, $i2+1);
    d('[' . $lev . '] ' . $e . ' => ' . $left . ' <span class="markup">(</span> ' . $ins . ' <span class="markup">)</span> ' . $rght);
    return solve($left . solve($ins) . $rght, $lev+1);
  } elseif (strpos($e, '|')!==false) {
    $i1 = strpos($e, '|');
    $i2 = strpos($e, '|', $i1+1);
    $ins = substr($e, $i1+1, $i2-$i1-1);
    $left = substr($e, 0, $i1);
    $rght = substr($e, $i2+1);
    d('[' . $lev . '] ' . $e . ' => ' . $left . ' <span class="markup">|</span> ' . $ins . ' <span class="markup">|</span> ' . $rght);
    return solve($left . vabs(solve($ins)) . $rght, $lev+1);
  } elseif (vstrpos($e, '+')!==false) {
    list ($e1, $e2) = vexplode('+', $e, 2);
    d('[' . $lev . '] ' . $e . ' => ' . $e1 . ' <span class="markup">+</span> ' . $e2);
    return vadd(solve($e1, $lev+1), solve($e2, $lev+1));
  } elseif (vstrpos($e, '-', 1)!==false) {
    list ($e1, $e2) = vexplode('-', $e, 2);
    d('[' . $lev . '] ' . $e . ' => ' . $e1 . ' <span class="markup">-</span> ' . $e2);
    return vsub(solve($e1, $lev+1), solve($e2, $lev+1));
  } elseif (vstrpos($e, '*')!==false) {
    list ($e1, $e2) = vexplode('*', $e, 2);
    d('[' . $lev . '] ' . $e . ' => ' . $e1 . ' <span class="markup">*</span> ' . $e2);
    return vmult(solve($e1, $lev+1), solve($e2, $lev+1));
  } elseif (vstrpos($e, '/')!==false) {
    list ($e1, $e2) = vexplode('/', $e, 2);
    d('[' . $lev . '] ' . $e . ' => ' . $e1 . ' <span class="markup">/</span> ' . $e2);
    return vdiv(solve($e1, $lev+1), solve($e2, $lev+1));
  } elseif (vstrpos($e, 'x')!==false) {
    list ($e1, $e2) = vexplode('x', $e, 2);
    d('[' . $lev . '] ' . $e . ' => ' . $e1 . ' <span class="markup">x</span> ' . $e2);
    return vcross(solve($e1, $lev+1), solve($e2, $lev+1));
  }
  if (isset($_SESSION['arystore'][$e])) return solve($_SESSION['arystore'][$e], $lev+1);
  /*  $as = $_SESSION['arystore'];
   uksort($as, create_function('$a, $b', 'return strlen($b)-strlen($a);'));
   foreach ($as as $i=>$v) {
   $e = str_ireplace($i, $v, $e);
   } */
  return $e;
}

$cmds = explode("\n", $cmd);

foreach ($cmds as $c) {
  $c = trim($c);
  if (empty($c)) continue;
  list ($op, $val) = explode(' ', $c, 2);
  switch (strtolower($op)) {
    case 'set':
      list ($idx, $vec) = explode('=', $val, 2);
      $idx = strtoupper(trim($idx));
      $vec = trim($vec);  // maybe add solve()
      $_SESSION['arystore'][$idx] = $vec;
      $out[] = '<span class="result">' . $vec . ' -> ' . $idx . '</span>';
      break;

    case 'unset':
      $idx = strtoupper($val);
      if (isset($_SESSION['arystore'][$idx])) {
        unset($_SESSION['arystore'][$idx]);
        $out[] = '<span class="result">Variable ' . $idx . ' deleted.</span>';
      } else {
        $out[] = '<span class="error">Variable ' . $idx . ' not found.</span>';
      }
      break;

    case 'dist':         // dist P1;P2
      list ($p1, $p2) = explode(';', $val, 2);
      $ans = solve('|' . $p2 . '-' . $p1 . '|');
      $_SESSION['arystore']['Ans'] = $ans;
      $out[] = '> <span class="result">' . $ans . '</span>';
      break;

    case 'distpg':       // distpg P;GP;GV
      list ($p, $gp, $gv) = explode(';', $val, 3);
      $b = solve('(' . $p . ')-(' . $gp . ')');
      $out[] = '(Vector P-GP is ' . $b . ')';
      $c = solve('(' . $gv . ')x' . $b);
      $out[] = '(GVxGP is ' . $c . ')';
      $ans = vabs($c)/vabs(solve($gv));
      $_SESSION['arystore']['Ans'] = $ans;
      $out[] = '> <span class="result">' . $ans . '</span>';
      break;

    case 'distpe':      // distpe P;EP;EV1;EV2
      list ($p, $ep, $ev1, $ev2) = explode(';', $val, 4);
      $n = solve('(' . $ev1 . ')x(' . $ev2 . ')');
      $out[] = '(Normalvector of E is ' . $n . ' with len ' . vabs($n) . ')';
      $nu = solve($n . '/' . vabs($n));
      $out[] = '(Normaluniformvector of E is ' . $nu . ' with len ' . vabs($nu) . ')';
      $tmp = solve('(' . $p . ')-(' . $ep . ')');
      $ans = abs(solve($tmp . '*' . $nu));
      $_SESSION['arystore']['Ans'] = $ans;
      $out[] = '> <span class="result">' . $ans . '</span>';
      break;

    case 'angle':      // angle A;B
      list ($a, $b) = explode(';', $val, 2);
      $c = solve('(' . $a . ')x(' . $b . ')');
      $out[] = '(Cross of ' . $a . ' and ' . $b . ' is ' . $c . ')';
      $cosv = solve('|' . $c . '|/(|' . $a . '|*|' . $b . '|)');
      $out[] = '(cos ß = ' . $cosv . ')';
      $ans = rad2deg(acos($cosv));
      $_SESSION['arystore']['Ans'] = $ans;
      $out[] = '> <span class="result">' . $ans . '°</span>';
      break;

    case 'anglege':    // anglege GP;GV;EP;EV1;EV2
      list ($gp, $gv, $ep, $ev1, $ev2) = explode(';', $val, 5);
      $n = solve('(' . $ev1 . ')x(' . $ev2 . ')');
      $out[] = '(Normalvector of E is ' . $n . ' with len ' . vabs($n) . ')';
      $tmp = solve($n . '*(' . $gv . ')');
      $out[] = '(' . $n . '*' . $gv . ' is ' . $tmp . ')';
      $sinv = solve($tmp . '/(|' . $gv . '|*|' . $n . '|)');
      $out[] = '(sin ß = ' . $sinv . ')';
      $ans = rad2deg(asin($sinv));
      $_SESSION['arystore']['Ans'] = $ans;
      $out[] = '> <span class="result">' . $ans . '°</span> (' . (90-$ans) . '°)';
      break;

    case 'angleee':    // anglege EP;EV1;EV2;EP;EV1;EV2
      list ($e1p, $e1v1, $e1v2, $e2p, $e2v1, $e2v2) = explode(';', $val, 6);
      $n1 = solve('(' . $e1v1 . ')x(' . $e1v2 . ')');
      $n2 = solve('(' . $e2v1 . ')x(' . $e2v2 . ')');
      $out[] = '(Normalvector of E1 is ' . $n1 . ' with len ' . vabs($n1) . ')';
      $out[] = '(Normalvector of E2 is ' . $n2 . ' with len ' . vabs($n2) . ')';
      $tmp = solve($n1 . '*' . $n2);
      $out[] = '(' . $n1 . '*' . $n2 . ' is ' . $tmp . ')';
      $cosv = solve($tmp . '/(|' . $n1 . '|*|' . $n2 . '|)');
      $out[] = '(cos ß = ' . $cosv . ')';
      $ans = rad2deg(acos($cosv));
      $_SESSION['arystore']['Ans'] = $ans;
      $out[] = '> <span class="result">' . $ans . '°</span> (' . (90-$ans) . '°)';
      break;

    case 'inter':     // inter G1P;G1V;G2P;G2V
      list ($g1p, $g1v, $g2p, $g2v) = explode(';', $val, 4);
      $g1ps = vsplit(solve($g1p));
      $g1vs = vsplit(solve($g1v));
      $g2ps = vsplit(solve($g2p));
      $g2vs = vsplit(solve($g2v));
      $matrix = array();
      foreach ($g1vs as $i=>$v) {
        $matrix[] = array($g1vs[$i],-$g2vs[$i],$g2ps[$i]-$g1ps[$i]);
      }
      $gsol = gauss($matrix);
      d('(Coefficients: t1=' . $gsol[0] . ', t2=' . $gsol[1] . ')', true);
      $ans = solve($g1p . '+' . $gsol[0] . '*' . $g1v);
      $ans2 = solve($g2p . '+' . $gsol[1] . '*' . $g2v);
      if ($ans === $ans2) {
        $_SESSION['arystore']['Ans'] = $ans;
        $out[] = '> <span class="result">' . $ans . '</span> (' . $ans2 . ')';
      } else {
        $out[] = '<span class="result">No intersection found! (' . $ans . ' / ' . $ans2 . ')</span>';
      }
      break;

    case 'interge':     // interge GP;GV;EP;EV1;EV2
      list ($gp, $gv, $ep, $ev1, $ev2) = explode(';', $val, 5);
      $gps = vsplit(solve($gp));
      $gvs = vsplit(solve($gv));
      $eps = vsplit(solve($ep));
      $ev1s = vsplit(solve($ev1));
      $ev2s = vsplit(solve($ev2));
      $matrix = array();
      foreach ($ev1s as $i=>$v) {
        $matrix[] = array($ev1s[$i],$ev2s[$i],-$gvs[$i],$gps[$i]-$eps[$i]);
      }
      $gsol = gauss($matrix);
      d('(Coefficients: t=' . $gsol[2] . ', r=' . $gsol[0] . ', s=' . $gsol[1] . ')', true);
      $ans = solve($gp . '+' . $gsol[2] . '*' . $gv);
      $ans2 = solve($ep . '+' . $gsol[0] . '*' . $ev1 . '+' . $gsol[1] . '*' . $ev2);
      if ($ans === $ans2) {
        $_SESSION['arystore']['Ans'] = $ans;
        $out[] = '> <span class="result">' . $ans . '</span> (' . $ans2 . ')';
      } else {
        $out[] = '<span class="result">No intersection found! (' . $ans . ' / ' . $ans2 . ')</span>';
      }
      break;

    case 'plotl':     // plotl P1;P2
      list ($p1, $p2) = explode(';', $val, 2);
      $_SESSION['plot'][] = 'L:' . $p1 . ';' . $p2;
      $out[] = '<span class="result">Plot: L: ' . $p1 . ' to ' . $p2 . '</span>';
      break;

    case 'plotg':     // plotg GP;GV
      list ($gp, $gv) = explode(';', $val, 2);
      $_SESSION['plot'][] = 'G:' . $gp . ';' . $gv;
      $out[] = '<span class="result">Plot: g: y=' . $gp . '+t*' . $gv . '</span>';
      break;

    case 'plote':     // plote EP;EV1;EV2
      list ($ep, $ev1, $ev2) = explode(';', $val, 3);
      $_SESSION['plot'][] = 'E:' . $ep . ';' . $ev1 . ';' . $ev2;
      $out[] = '<span class="result">Plot: E: y=' . $ep . '+r*' . $ev1 . '+s*' . $ev2 . '</span>';
      break;

    case 'plotdim':   // plotdim minx;maxx;miny;maxy
      if ($val == 'auto') {
        unset($_SESSION['plotdim']);
        break;
      }
      list ($xn, $xx, $yn, $yx) = explode(';', $val, 4);
      $_SESSION['plotdim'] = $xn . ';' . $xx . ';' . $yn . ';' . $yx;
      $out[] = '<span class="result">Plot dimensions set to: [' . $xn . '..' . $xx . '], [' . $yn . '..' . $yx . ']</span>';
      break;

    case 'plotdel':   // plotdel idx
      if ($val == 'all') {
        $_SESSION['plot'] = array();
        break;
      }
      if (isset($_SESSION['plot'][$val])) {
        unset($_SESSION['plot'][$val]);
        $out[] = '<span class="result">Plot #' . $val . ' deleted.</span>';
      } else {
        $out[] = '<span class="error">Plot #' . $val . ' not found.</span>';
      }
      break;

    default:
      $ans = solve($c);
      $_SESSION['arystore']['Ans'] = $ans;
      $out[] = '> <span class="result">' . $ans . '</span>';
  }
}

$_SESSION['lastcmd'] = $cmd;

foreach ($_SESSION['plot'] as $i=>$v) {
  foreach ($_SESSION['plot'] as $j=>$w) {
    if ($w == $v && $j != $i) unset($_SESSION['plot'][$j]);
  }
}

?>
<html>
<head>
<title>Vector Calculator</title>
<style type="text/css">
TABLE.help,TABLE.vars,TABLE.plots {
	font-size: 8pt;
	background-color: #ccc;
	margin: 2px;
}

TABLE.vars,TABLE.plots {
	float: left;
}

TABLE.help TD {
	background-color: #ffc;
}

TABLE.vars TD {
	background-color: #ddf;
}

TABLE.plots TD {
	background-color: #cfc;
}

PRE.sol {
	background-color: #020;
	color: lime;
	border: 1px solid black;
}

.result {
	color: yellow;
}

.markup {
	color: white;
}

.error {
	color: red;
}
</style>
</head>
<body>
<?php
echo '<a href="' . $_SERVER['PHP_SELF'];
if (!isset($_GET['dbg'])) echo '?dbg';
echo '">Debug ' . ((isset($_GET['dbg']))?'off':'on') . '</a>';
?> / <a href="https://github.com/mbirth/php-vectcalc">GitHub</a>
<table border="0" class="vars" cellpadding="0" cellspacing="1">
	<tr>
		<th colspan="2">Variables</th>
	</tr>
	<?php
	foreach ($_SESSION['arystore'] as $i=>$a) {
	  echo '<tr><td>' . $i . '</td><td>' . $a . '</td></tr>';
	}
	?>
</table>
<table class="plots" border="0" cellpadding="0" cellspacing="1">
	<tr>
		<th colspan="2">Plots</th>
	</tr>
	<?php
	foreach ($_SESSION['plot'] as $i=>$p) {
	  echo '<tr><td>#' . $i . '</td><td>' . $p . '</td></tr>';
	}
	?>
</table>
<table border="0" class="help" cellpadding="0" cellspacing="1">
	<tr>
		<th colspan="2">Help</th>
	</tr>
	<tr>
		<td><tt><i>equation</i></tt></td>
		<td>calculates the result of <i>equation</i></td>
	</tr>
	<tr>
		<td><tt>set <i>idx</i>=<i>equation</i></tt></td>
		<td>sets variable <i>idx</i> to the result of <i>equation</i></td>
	</tr>
	<tr>
		<td><tt>unset <i>idx</i></tt></td>
		<td>deletes variable <i>idx</i></td>
	</tr>
	<tr>
		<td><tt>plotL <i>P1</i>;<i>P2</i></tt></td>
		<td>plots line between points <i>P1</i> and <i>P2</i></td>
	</tr>
	<tr>
		<td><tt>plotG <i>Gp</i>;<i>Gv</i></tt></td>
		<td>plots g: y=<i>Gp</i>+t*<i>Gv</i></td>
	</tr>
	<tr>
		<td><tt>plotE <i>Ep</i>;<i>Ev1</i>;<i>Ev2</i></tt></td>
		<td>plots E: y=<i>Ep</i>+r*<i>Ev1</i>+s*<i>Ev2</i></td>
	</tr>
	<tr>
		<td><tt>plotdim <i>minx</i>;<i>maxx</i>;<i>miny</i>;<i>maxy</i></tt>
		or<br />
		<tt>plotdim auto</tt></td>
		<td>sets dimensions of plot to [<i>minx</i>..<i>maxx</i>], [<i>miny</i>..<i>maxy</i>]
		or to automatic</td>
	</tr>
	<tr>
		<td><tt>plotdel <i>idx</i></tt> or<br />
		<tt>plotdel all</tt></td>
		<td>removes plotted item numer <i>idx</i> or all</td>
	</tr>
	<tr>
		<td><tt>dist <i>P1</i>;<i>P2</i></tt></td>
		<td>distance of <i>P1</i> and <i>P2</i></td>
	</tr>
	<tr>
		<td><tt>distPG <i>P</i>;<i>Gp</i>;<i>Gv</i></tt></td>
		<td>distance of <i>P</i> from g: y=<i>Gp</i>+t*<i>Gv</i></td>
	</tr>
	<tr>
		<td><tt>distPE <i>P</i>;<i>Ep</i>;<i>Ev1</i>;<i>Ev2</i></tt></td>
		<td>distance of <i>P</i> from E: y=<i>Ep</i>+r*<i>Ev1</i>+s*<i>Ev2</i></td>
	</tr>
	<tr>
		<td><tt>angle <i>V1</i>;<i>V2</i></tt></td>
		<td>angle in degrees between <i>V1</i> and <i>V2</i></td>
	</tr>
	<tr>
		<td><tt>angleGE <i>Gp</i>;<i>Gv</i>;<i>Ep</i>;<i>Ev1</i>;<i>Ev2</i></tt></td>
		<td>angle in degrees between g: y=<i>Gp</i>+t*<i>Gv</i> and E: y=<i>Ep</i>+r*<i>Ev1</i>+s*<i>Ev2</i></td>
	</tr>
	<tr>
		<td><tt>angleEE <i>E1p</i>;<i>E1v1</i>;<i>E1v2</i>;<i>E2p</i>;<i>E2v1</i>;<i>E2v2</i></tt></td>
		<td>angle in degrees between two E: y=<i>Enp</i>+r*<i>Env1</i>+s*<i>Env2</i></td>
	</tr>
	<tr>
		<td><tt>inter <i>G1p</i>;<i>G1v</i>;<i>G2p</i>;<i>G2v</i></tt></td>
		<td>intersection point of two g: y=<i>Gnp</i>+t*<i>Gnv</i></td>
	</tr>
	<tr>
		<td><tt>interGE <i>Gp</i>;<i>Gv</i>;<i>Ep</i>;<i>Ev1</i>;<i>Ev2</i></tt></td>
		<td>intersection point between g: y=<i>Gp</i>+t*<i>Gv</i> and E: y=<i>Ep</i>+r*<i>Ev1</i>+s*<i>Ev2</i></td>
	</tr>
</table>
<img
	src="<?php echo $_SERVER['PHP_SELF'] . '?plotme' . ((isset($_GET['dbg']))?'&dbg':''); ?>"
	style="float: right;" />
<form method="post"><textarea name="cmd" rows="10" cols="72"><?php echo $cmd; ?></textarea><br />
<input type="submit" value="Send" /> <input type="button" value="Clear"
	onClick="document.forms[0].cmd.value = '';" /> <input type="reset"
	value="Reset" /></form>
<pre class="sol">
<?php
echo $cmd . CRLF . str_repeat('=', 80) . CRLF;
foreach ($out as $o) {
  echo $o . CRLF;
}
?>
</pre>
<script type="text/javascript">
  document.forms[0].cmd.select();
  document.forms[0].cmd.focus();
</script>
</body>
</html>
