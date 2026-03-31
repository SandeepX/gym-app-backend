<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Http\Response;
use RuntimeException;

class MemberService
{
    public function getMemberDetailById($memberId, $with = [])
    {
        $member = Member::with($with)->find($memberId);

        if (! $member) {
            throw new RuntimeException('Member Detail Not Found', Response::HTTP_NOT_FOUND);
        }

        return $member;
    }


}
