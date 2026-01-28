<?php

namespace CxReports\Models;

enum DocumentFIleFormat{
    case pdf; 
    case docx;
    case xlsx;
    case pptx; 
    case html;
}