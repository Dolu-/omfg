<?php
/*
 * Copyright (c) 2004-2008 sk89q <https://www.sk89q.com/2008/08/ttf-textbox-in-php/>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * The name of sk89q may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY <copyright holder> ''AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <copyright holder> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

define("ALIGN_LEFT", "left");
define("ALIGN_CENTER", "center");
define("ALIGN_RIGHT", "right");
define("VALIGN_TOP", "top");
define("VALIGN_MIDDLE", "middle");
define("VALIGN_BOTTOM", "bottom");
define("ORIENTATION_TOP", "top");
define("ORIENTATION_BOTTOM", "bottom");
define("ORIENTATION_LEFT", "left");
define("ORIENTATION_RIGHT", "right");
define("ORIENTATION_CENTER", "center");

// v1.9.1 (small changes from original version v1.9)
// Added emphasys "..." when text is truncated (no option).
function imagettftextboxopt(&$image, $size, $angle, $left, $top, $color, $fontfile, $text, array $opt = array())
{
    // Default options
    $default_opt = array(
        // bbox/position
        'width' => 200, // Required
        'height' => 0, // Optional height
        'orientation' => array(ORIENTATION_TOP, ORIENTATION_LEFT), // Orientation for placement
                                                                   // Can be a constant or a nubmer
                                                                   // relative to 0, 0 of the box
        'indent_for_angle' => true, // For angled text, have the left x of each line be translated too
        // General
        'space_char' => ' ', // Space character
        'use_mbstring' => false, // Uses the mbstring extension for string functions (Unicode, etc. support)
        // Text
        'align' => ALIGN_LEFT, // ALIGN_LEFT, ALIGN_MIDDLE, ALIGN_RIGHT
        'line_height' => 0, // Set to 0 to use the largest line height (not a good idea)
        'v_align' => VALIGN_TOP, // VALIGN_TOP, VALIGN_CENTER, VALIGN_BOTTOM
        'leading' => 0,
        'show_3dots' => true,
        // Features
        'outlines' => array(), // Outlines: array(weight, color)
        // Word wrap
        'force_word_wrap' => true, // If a word is too long, you can force it to wrap
                                   // However, to do this, it will imagettfbbox() every character
        'cutoff_word_wrap' => false, // Instead of wrapping, cut off the text
        'word_wrap_hyphen' => '-', // When a word is force wrapped, this character will be appended
        'aggressive_word_wrap' => false, // Aggressive word wrapping will force a wrap if too much of a line is
                                         // left blank (aggressive_word_wrap_amt) - great for Chinese and other
                                         // languages where words are characters, but not for other languages
        'aggressive_word_wrap_amt' => .03, // If aggressive forced word wrapping is enabled, this is the 
                                           // maximum percent width of a line that may be left blank
        );
    
    // "Named arguments"
    extract($default_opt);
    extract($opt);
    
    // Use mb_string?
    if ($use_mbstring) {
        if (!extension_loaded("mbstring")) {
            throw new Exception("The mbstring module is not loaded");
        }
        
        $F_strlen = "mb_strlen";
        $F_substr = "mb_substr";
    } else {
        $F_strlen = "strlen";
        $F_substr = "substr";
    }
    
    // Check input
    if ($v_align != VALIGN_TOP && $v_align != VALIGN_MIDDLE && $v_align != VALIGN_BOTTOM) {
        throw new Exception("Unknown vertical alignment passed");
    }
    if ($align != ALIGN_LEFT && $align != ALIGN_CENTER && $align != ALIGN_RIGHT) {
        throw new Exception("Unknown horizontal alignment passed");
    }
    if (count($outlines) > 0 && !is_array($outlines[0])) {
        throw new Exception("The outline argument uses nested arrays");
    }
        
    // Adds support for manual line breaks
    $text_lines = explode("\n", $text); 
    
    $lines = array(); // Stores each line
    $line_widths = array(); // Stores how wide each line is, for alignment calculation
    $line_heights = array(); // Stores how tall each line is, for alignment calculation
    
    $largest_line_height = 0; // Auto-detected line height
    
    // First, go through all the lines
    for ($l = 0; $l < count($text_lines); $l++) {
        $block = $text_lines[$l];
        $words = explode($space_char, $block);
        
        $buffer = ''; // Stores the current line that's been checked to be <= $width
        $buffer_width = 0; // Width corresponding to the buffer
        $buffer_height = 0; // Height corresponding to the buffer
        $test_word = ''; // Current word being 'tested'
        $test_buffer = ''; // Contains the current line that's being tested, that's not guaranteed to be <= $width
        $test_width = 0;
        $test_height = 0;
        
        // Iterate through all the words
        for ($w = 0; $w <= count($words); $w++) {
            $test_word = $words[$w];
            $test_buffer = $buffer.($buffer == "" ? "" : " ").$test_word;
            
            $bbox = imagettfbbox($size, 0, $fontfile, $test_buffer);
            $test_width = $bbox[2]-$bbox[0];
            $test_height = $bbox[1]-$bbox[7];
            
            // For auto line height detection
            if ($test_height > $largest_line_height) {
                $largest_line_height = $test_height;
            }
            
            // Tested line is too long, meaning the buffer is what we want as a new line
            // OR we're on the last word
            if ($test_width > $width || $w == count($words)) {
                // Slightly (but not much) smarter word wrapping
                if ($aggressive_word_wrap && !empty($buffer) && $w != count($words)) {
                    if ($buffer_width < $width-($aggressive_forced_wrap_amt*$width)) {
                        $buffer = ""; // Forces word wrapping
                    }
                }
                
                // This single word line is already too long by itself
                // We can force a word wrap
                if (empty($buffer)) {
                    // Cut this word into two chunks to force it to wrap
                    if ($force_word_wrap) {
                        $test_length = $F_strlen($test_buffer);
                        
                        $c_buffer_width = 0;
                        $c_buffer_height = 0;
                        $c_test_buffer = '';
                        $c_test_width = 0;
                        $c_test_height = 0;
                        
                        // Iterate through all the characters
                        for ($c = 0; $c < $test_length; $c++) {     
                            $c_test_buffer = trim($F_substr($test_buffer, 0, $c+1), $space_char).$word_wrap_hyphen;
                            
                            $bbox = imagettfbbox($size, 0, $fontfile, $c_test_buffer);
                            $c_test_width = $bbox[2]-$bbox[0];
                            $c_test_height = $bbox[1]-$bbox[7];
            
                            // For auto line height detection
                            if ($c_test_height > $largest_line_height) {
                                $largest_line_height = $c_test_height;
                            }
                            
                            if ($c_test_width > $width) {
                                $c_buffer = $F_substr($test_buffer, 0, $c).$word_wrap_hyphen;
                                if (empty($c_buffer)) { // $width too small
                                    throw new Exception("Width $width is too small; increase it or disable forced word wrapping");
                                } else {
                                    $lines[] = $c_buffer; // Add the line
                        
                                    // Needed for alignment
                                    $line_widths[] = $c_buffer_width;
                                    $line_heights[] = $c_buffer_height;
                                    
                                    if (!$cutoff_word_wrap) {
                                        // Add the rest as a new word
                                        $words[$w--] = $F_substr($test_buffer, $c);
                                    }
                                    
                                    // Stop calculating lines if we've already reached max height
                                    if ($height > 0 && ($line_height > 0 ? count($lines)*$line_height : array_sum($line_heights)) > $height) {
                                        break 2;
                                    }
                                    
                                    break;
                                }
                            } else {
                                $c_buffer_width = $c_test_width;
                                $c_buffer_height = $c_test_height;
                            }
                        }
                    // Or we don't
                    } else {
                        $lines[] = $test_buffer; // Add the buffer to the list of lines
                        $buffer = "";
                        
                        // Needed for alignment
                        $line_widths[] = $test_width;
                        $line_heights[] = $test_height;
                                    
                        // Stop calculating lines if we've already reached max height
                        if ($height > 0 && ($line_height > 0 ? count($lines)*$line_height : array_sum($line_heights)) > $height) {
                            break;   
                        }
                    }
                // Line fits well
                } else {
                    $lines[] = $buffer; // Add the buffer to the list of lines
                    $buffer = "";
                    $w--;
                    
                    // Needed for alignment
                    $line_widths[] = $buffer_width;
                    $line_heights[] = $buffer_height;
                                    
                    // Stop calculating lines if we've already reached max height
                    if ($height > 0 && ($line_height > 0 ? count($lines)*$line_height : array_sum($line_heights)) > $height) {
                        break;   
                    }
                }
            // Still not long enough... let's try again
            } else {
                $buffer = $test_buffer;
                $buffer_width = $test_width;
                $buffer_height = $test_height;
            }
        }
    }
    
    // No line height given, so we will use our auto-detected line height for it
    if ($line_height == 0) {
        $line_height = $largest_line_height;
    }
    
    // Add leading to the line height
    $line_height += $leading;
    
    // Number of lines and total height used for vertical
    // alignment and for the returned value
    $max_lines = $height > 0 ? floor($height/$line_height) : count($lines);
    $drawn_lines = count($lines) < $max_lines ? count($lines) : $max_lines;
    $total_height = $drawn_lines * $line_height;
	
    if (($show_3dots) && ($drawn_lines != count($lines))) {
      $lines[$max_lines - 1] = trim($lines[$max_lines - 1], " .\n\r")."...";
    }
	
    $top_shift = 0;
    $left_shift = 0;
    
    // Slightly adjust position based on the first line
    $first_line_bbox = imagettfbbox($size, 0, $fontfile, $lines[0]);
    
    // Vertical alignment
    if ($height != 0) {
        // Make sure the first line fits correctly;
        $first_line_offset = $line_height-$line_heights[0];
        
        if ($v_align == VALIGN_TOP) {
            $top_shift += abs($first_line_bbox[7])-$line_height;
        } else if ($v_align == VALIGN_MIDDLE) {
            $top_shift += ($height-$total_height-$first_line_offset)/2;
        } else if ($v_align == VALIGN_BOTTOM) {
            $top_shift += ($height-$total_height-$first_line_offset);
        }
    }
    
    // Draw iterations, used for outlines
    $draw_iterations = array_merge($outlines, array(array(0, $color)));
    
    // Orientation
    if ($orientation[1] == ORIENTATION_LEFT) {
        $orient_left = 0;
    } else if ($orientation[1] == ORIENTATION_RIGHT) {
        $orient_left = $width;
    } else if ($orientation[1] == ORIENTATION_CENTER) {
        $orient_left = $width/2;
    } else {
        $orient_left = intval($orientation[1]);
    }
    
    if ($orientation[0] == ORIENTATION_TOP) {
        $orient_top = 0;
    } else if ($orientation[0] == ORIENTATION_BOTTOM) {
        $orient_top = $height;
    } else if ($orientation[0] == ORIENTATION_CENTER) {
        $orient_top = $height/2;
    } else {
        $orient_top = intval($orientation[0]);
    }
    
    // Go through each draw iteration
    foreach ($draw_iterations as $draw) {
        list($weight, $color) = $draw;
        
        // Go through each X and Y to draw the outline :/
        // Not the world's most efficient method...
        for ($leftc = $left-$weight; $leftc <= $left+$weight; $leftc++) {
            for ($topc = $top-$weight; $topc <= $top+$weight; $topc++) {
                // Now go through and print all the lines
                for ($l = 0; $l < $drawn_lines; $l++) {
                    $line = $lines[$l];
                    
                    $top_offset = 0;
                    $left_offset = 0;
                    
                    // Horizontal alignment, more accurate than the vertical
                    // alignment we have above
                    if ($align == ALIGN_LEFT) {
                        $left_offset = -$first_line_bbox[0];
                    } else if ($align == ALIGN_CENTER) {
                        $left_offset = ($width-$line_widths[$l])/2;
                    } else if ($align == ALIGN_RIGHT) {
                        $left_offset = ($width-$line_widths[$l]);
                    }
                    
                    // Where we'll be drawing it
                    $x = $leftc+$left_offset+$left_shift;
                    $y = $topc+$top_offset+$top_shift+$line_height+($line_height*$l);
                    
                    // We need to figure the position if the angle is not zero
                    if ($indent_for_angle && ($angle != 0 || $orient_left != 0 || $orient_top != 0)) {
                        // Translate the point
                        $x -= $left+$orient_left;
                        $y -= $top+$orient_top;
                        $new_x = $x*cos(-$angle*M_PI/180)-$y*sin(-$angle*M_PI/180);
                        $new_y = $x*sin(-$angle*M_PI/180)+$y*cos(-$angle*M_PI/180);
                        $x = $new_x+$left;
                        $y = $new_y+$top;
                    }
                    imagettftext($image, $size, $angle, $x, $y, $color, $fontfile, $line);
                }
            }
        }
    }
    return $total_height; // Total height
}
?>
