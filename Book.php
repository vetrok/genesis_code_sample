<?php

namespace App\Books;

use App\Books\ApiReflections\Supadu;
use App\Books\ApiReflections\Tastekid;
use App\ConversationTree\Steps\CustomSortFilterStep;
use App\Models\CollectionSupaduBook;
use App\Models\FormatSupaduBook;
use App\Models\FullJoinBook;
use App\Models\PanmcmillanBook;
use App\Models\PanmcmillanFormat;
use App\Models\FormatPanmcmillanBook;
use App\Models\SupaduBook;
use App\Models\SupaduCategory;
use App\Models\SupaduCollection;
use App\Models\SupaduKeyword;
use App\Models\SupaduFormat;
use Illuminate\Support\Collection;

/**
 * Class Book
 *
 * Manipulating with Books data and returns response in required format
 *
 * @package App\Books
 */
class Book
{
    /**
     * Paper books formats
     */
    const PAPER_BOOKS = ['BC', 'BB'];
    const ELECTRO_BOOKS = ['DG'];

    /**
     * @var int - text max length
     */
    protected static $book_description_length = 320;


    /**
     * Get latest books
     *
     * For param values @see Book::performComposedRequest
     *
     * @param null $filterName
     * @param null $filterValue
     * @param null $sortValue
     * @param null $sortSide
     * @param int $offset
     * @param int $limit
     * @param bool $anyFormat
     * @return array
     */
    public static function getLatest(
        $filterName = null,
        $filterValue = null,
        $sortValue = null,
        $sortSide = null,
        $offset = 0,
        $limit = 5,
        $anyFormat = true
    )
    {
        if ($sortValue === null) {
            $sortValue = CustomSortFilterStep::SORT_BY_NEWEST;
            $sortSide = 'desc';
        }

        $latestBooksCollection = static::performComposedRequest(
            null,
            $filterName,
            $filterValue,
            $sortValue,
            $sortSide,
            null,
            $offset,
            $limit,
            $anyFormat
        );

        return static::makeCarouselFromCollection($latestBooksCollection);
    }


    /**
     * Get books with non fiction genre
     *
     * For param values @see Book::performComposedRequest
     *
     * @param null $filterName
     * @param null $filterValue
     * @param null $sortValue - values == publication_date|price|average_rating
     * @param string $sortSide - values = asc|desc
     * @param int $offset
     * @param int $limit
     * @param bool $anyFormat
     * @return array|mixed
     */
    public static function getNonFiction(
        $filterName = null,
        $filterValue = null,
        $sortValue = null,
        $sortSide = 'asc',
        $offset = 0,
        $limit = 5,
        $anyFormat = true
    )
    {
        $booksCollection = static::getBooksWithSpecialSorting(
            false,
            $filterName,
            $filterValue,
            $sortValue,
            $sortSide,
            null,
            $offset,
            $limit,
            $anyFormat);

        return static::makeCarouselFromCollection($booksCollection);
    }

    /**
     * Get books with fiction books
     *
     * For param values @see Book::performComposedRequest
     *
     * @param null $filterName
     * @param $filterValue
     * @param null $sortValue
     * @param string $sortSide
     * @param int $offset
     * @param int $limit
     * @param bool $anyFormat
     * @return array|mixed
     */
    public static function getFiction(
        $filterName = null,
        $filterValue = null,
        $sortValue = null,
        $sortSide = 'asc',
        $offset = 0,
        $limit = 5,
        $anyFormat = true
    )
    {
        $books = static::getBooksWithSpecialSorting(
            true,
            $filterName,
            $filterValue,
            $sortValue,
            $sortSide,
            null,
            $offset,
            $limit,
            $anyFormat
        );

        return static::makeCarouselFromCollection($books);
    }

    /**
     * Get books with maximal settings and create carousel fro it
     *
     * @param $fictionFlag
     * @param $filterName
     * @param $filterValue
     * @param $sortValue
     * @param $sortSide
     * @param $age
     * @param $offset
     * @param $limit
     * @param $anyFormat
     * @return array
     */
    public static function getBooksCollectionWithSpecialSorting(
        $fictionFlag = null,
        $filterName = null,
        $filterValue = null,
        $sortValue = null,
        $sortSide = null,
        $age = null,
        $offset = 0,
        $limit = 5,
        $anyFormat = true
    )
    {
        $books = static::getBooksWithSpecialSorting(
            $fictionFlag,
            $filterName,
            $filterValue,
            $sortValue,
            $sortSide,
            $age,
            $offset,
            $limit,
            $anyFormat
        );

        return static::makeCarouselFromCollection($books);
    }

    /**
     * Get books filtered by age
     *
     * For param values @see Book::performComposedRequest
     *
     * @param int $ageFrom
     * @param int $ageTo
     * @param null $filterName
     * @param null $filterValue
     * @param null $sortValue
     * @param string $sortSide
     * @param int $offset
     * @param int $limit
     * @param bool $anyFormat
     * @return array|mixed
     */
    public static function getAgeBased(
        $ageFrom,
        $ageTo,
        $filterName = null,
        $filterValue = null,
        $sortValue = null,
        $sortSide = null,
        $offset = 0,
        $limit = 5,
        $anyFormat = true
    )
    {
        $age['from'] = $ageFrom;
        $age['to'] = $ageTo;
        if ($sortValue === null and $filterName === null) {
            $booksCollection = static::getTwoBooksSortedByDateAndRestByRand(
                null,
                null,
                null,
                $age,
                $offset,
                $limit,
                $anyFormat
            );
        } else {
            $booksCollection = static::performComposedRequest(
                null,
                $filterName,
                $filterValue,
                $sortValue,
                $sortSide,
                $age,
                $offset,
                $limit,
                $anyFormat
            );
        }

        return static::makeCarouselFromCollection($booksCollection);
    }

    /**
     * Get extract view
     *
     * @param $isbn13
     * @param $isBookForMe
     * @return array|null
     */
    public static function extractView($isbn13, $isBookForMe)
    {
        $bookFromDb = FullJoinBook::findBookByIsbn13(FullJoinBook::query(), $isbn13);

        if (empty($bookFromDb)) {

            return null;
        }

        $bookData = [];
        $bookData['isbn13'] = $bookFromDb['supadu_books_isbn13'] ?? $bookFromDb['panmcmillan_books_isbn13'];

        //Pretty formatted string
        $bookData['extract'] = static::prettyStringCutter(static::clearString(
            $bookFromDb['panmcmillan_books_extract_html']??
            $bookFromDb['supadu_books_description']
        ));
        $bookData['amazon_url'] = static::checkForAmazonUrl($bookData['isbn13'], $isBookForMe);

        $readMoreLink = null;
        if (!empty($bookFromDb['panmcmillan_books_isbn13'])) {
            $readMoreLink = static::getPanmcmillanLink($bookFromDb['panmcmillan_books_isbn13']);
        }

        $bookData['read_more_url'] = $readMoreLink;

        return $bookData;
    }

    /**
     * Get details for specified book
     *
     * @param $isbn13
     * @param bool $anyFormat
     * @return array|null
     */
    public static function bookDetail($isbn13, $anyFormat = true)
    {
        $bookFromDb = FullJoinBook::findBookByIsbn13(FullJoinBook::query(), $isbn13);

        if (empty($bookFromDb)) {

            return null;
        }

        $bookData = [];
        $bookData['isbn13'] = $bookFromDb['supadu_books_isbn13'] ?? $bookFromDb['panmcmillan_books_isbn13'];
        $bookData['description'] = static::prettyStringCutter(static::clearString(
            $bookFromDb['panmcmillan_books_keynote']??
            $bookFromDb['supadu_books_tagline']
        ));
        $bookData['amazon_url'] = static::checkForAmazonUrl($bookData['isbn13'], $anyFormat);
        $bookData['extract_exists'] = !empty($bookFromDb['panmcmillan_books_extract_html']) ? true : false;

        return $bookData;
    }

    /**
     * Check for proper amazon url
     *
     * @param $isbn13
     * @param bool $anyFormat
     * @return string
     */
    public static function checkForAmazonUrl($isbn13, $anyFormat = true)
    {
        if ($anyFormat == true) {
            return static::getAmazonLink($isbn13);
        } else {
            return static::getAmazonLinkFromPivot($isbn13);
        }
    }

    /**
     * Find books in Tastekid API and match theme with Panmcmillan API books
     *
     * @param string $author
     * @param bool|null $fictionFlag
     * @param int $offset
     * @param int $limit
     * @param bool $anyFormat
     * @return Collection
     */
    public static function getSimilarAuthors($author, $fictionFlag = null, $offset = 0, $limit = 3, $anyFormat = true)
    {
        $teskedApi = new Tastekid();
        $teskedApi->setType('authors');
        $teskedApi->setQuery($author);
        $authors = $teskedApi->getFirstPage();

        if (empty($authors['Similar']['Results'])) {
            return collect();
        }

        $query = FullJoinBook::query()->select('name as n1')->addSelect('panmcmillan_books_author as n2');

        if ($fictionFlag !== null) {
            $query = FullJoinBook::whereHasCategory($query, 'fiction', $fictionFlag);
        }

        //Filter books by format
        if ($anyFormat !== true) {

            $query = FullJoinBook::whereHasFormats($query, static::PAPER_BOOKS);
        }

        $query = FullJoinBook::joinWithSupaduAthors($query);
        $similarAuthors = $authors['Similar']['Results'];
        $similarAuthorsArray = array_column($similarAuthors, 'Name');
        $query->where(function ($query) use ($similarAuthorsArray) {
            $query->whereIn('panmcmillan_books_author', $similarAuthorsArray);
            $query->orWhereIn('name', $similarAuthorsArray);
        });

        $books = $query->orderBy(PanmcmillanBook::TABLE_NAME . '_author', 'asc')
            ->limit($limit)
            ->offset($offset)
            //Distinct names
            ->distinct()
            ->get();

        if (!empty($books)) {
            $authors = collect();
            foreach ($books as $key => $singleBook) {
                $authors[$key] = collect();
                $authors[$key]['name'] = $singleBook['n1'] ?? $singleBook['n2'];
            }
        }

        return $authors;
    }

    /**
     * Strings cant be more, than defined value, cut string in pretty format
     *
     * @param $string
     * @param null $maxStrLength
     * @return string
     */
    public static function prettyStringCutter($string, $maxStrLength = null)
    {
        if ($maxStrLength === null) {
            $maxStrLength = static::getDescriptionLength();
        }

        $formattedString = $string;
        if (mb_strlen($string) > $maxStrLength) {
            $cutString = mb_substr($string, 0, $maxStrLength);
            $formattedString = mb_substr($cutString, 0, $maxStrLength - 3) . '...';
        }

        return $formattedString;
    }

    /**
     * Makes collection in to carousel objects
     *
     * @param Collection $books
     * @return array
     */
    protected static function makeCarouselFromCollection($books)
    {
        $booksCarousel = [];
        if (!$books->isEmpty()) {
            foreach ($books as $key => $singleBook) {
                $booksCarousel[$key]['isbn13'] =
                    $singleBook[SupaduBook::TABLE_NAME . '_isbn13'] ??
                    $singleBook[PanmcmillanBook::TABLE_NAME . '_isbn13'];

                if (!empty($singleBook[SupaduBook::TABLE_NAME . '_id'])) {
                    $authorsSupadu = implode(
                        ', ', SupaduBook::find($singleBook[SupaduBook::TABLE_NAME . '_id'])->
                    supaduAuthors()
                        ->get()
                        ->pluck('name')
                        ->toArray());
                }

                if (empty($authorsSupadu)) {
                    $booksCarousel[$key]['author'] = $singleBook[PanmcmillanBook::TABLE_NAME . '_author'];
                } else {
                    $booksCarousel[$key]['author'] = $authorsSupadu;
                }

                //Get image
                if ($singleBook[PanmcmillanBook::TABLE_NAME . '_stored_image']
                    and file_exists(PanmcmillanBook::getImageDirFullPath() . $singleBook[PanmcmillanBook::TABLE_NAME . '_stored_image'])
                ) {

                    $booksCarousel[$key]['image_url'] =
                        url('/') . '/' . PanmcmillanBook::$imageDir . $singleBook[PanmcmillanBook::TABLE_NAME . '_stored_image'];

                } elseif ($singleBook[SupaduBook::TABLE_NAME . '_stored_image']
                    and file_exists(SupaduBook::getImageDirFullPath() . $singleBook[SupaduBook::TABLE_NAME . '_stored_image'])
                ) {

                    $booksCarousel[$key]['image_url'] =
                        url('/') . '/' . SupaduBook::$imageDir . $singleBook[SupaduBook::TABLE_NAME . '_stored_image'];

                } else {
                    $booksCarousel[$key]['image_url'] = $singleBook[PanmcmillanBook::TABLE_NAME . '_jacket_url'] ??
                        $singleBook[SupaduBook::TABLE_NAME . '_image'];
                }

                $title = $singleBook[PanmcmillanBook::TABLE_NAME . '_title'] ?? $singleBook[SupaduBook::TABLE_NAME . '_title'];
                $booksCarousel[$key]['title'] = static::prettyStringCutter(static::clearString($title), 80);
                $booksCarousel[$key]['price'] = $singleBook[SupaduBook::TABLE_NAME . '_price'] ?? null;
                $booksCarousel[$key]['rating'] = $singleBook[PanmcmillanBook::TABLE_NAME . '_average_rating'] ??
                    $singleBook[SupaduBook::TABLE_NAME . '_average_rating'];
            }
        }

        return $booksCarousel;
    }

    /**
     * Some requests requires special sorting
     *
     * For param values @see Book::performComposedRequest
     *
     * @param bool|null $fictionFlag
     * @param string|null $filterName
     * @param string|null $filterValue
     * @param string|null $sortValue
     * @param string|null $sortSide
     * @param array|null $age
     * @param int $offset
     * @param int $limit
     * @param bool $anyFormat
     * @return Collection
     */
    public static function getBooksWithSpecialSorting(
        $fictionFlag = null,
        $filterName = null,
        $filterValue = null,
        $sortValue = null,
        $sortSide = null,
        $age = null,
        $offset = 0,
        $limit = 5,
        $anyFormat = true
    )
    {
        if ($filterName === null and $sortValue === null) {
            //Newest sort
            $booksCollection = static::performComposedRequest(
                $fictionFlag,
                $filterName,
                $filterValue,
                CustomSortFilterStep::SORT_BY_NEWEST,
                'asc',
                $age,
                $offset,
                $limit,
                $anyFormat
            );
        } elseif ($sortValue === null) {
            //If filtering but not sorting
            $booksCollection = static::getTwoBooksSortedByDateAndRestByRand(
                $fictionFlag,
                $filterName,
                $filterValue,
                $age,
                $offset,
                $limit,
                $anyFormat
            );
        } else {
            //Custom sort
            $booksCollection = static::performComposedRequest(
                $fictionFlag,
                $filterName,
                $filterValue,
                $sortValue,
                $sortSide,
                $age,
                $offset,
                $limit,
                $anyFormat
            );
        }

        return $booksCollection;
    }


    /**
     * Perform 2xPublish date sort and Rest Random sort
     *
     * For param values @see Book::performComposedRequest
     *
     * @param bool|null $fictionFlag
     * @param string|null $filterName
     * @param string|null $filterValue
     * @param array|null $age - array ['from']|['to']
     * @param int $offset
     * @param int $limit
     * @param bool $anyFormat
     * @return Collection
     */
    protected static function getTwoBooksSortedByDateAndRestByRand(
        $fictionFlag = null,
        $filterName = null,
        $filterValue = null,
        $age = null,
        $offset = 0,
        $limit = 5,
        $anyFormat = true
    )
    {
        //Date sorting
        $booksCollectionByDate = static::performComposedRequest(
            $fictionFlag,
            $filterName,
            $filterValue,
            'publication_date',
            'desc',
            $age,
            $offset,
            100,
            $anyFormat
        );
        if ($booksCollectionByDate->isEmpty()) {

            return collect();
        }

        $allBooks = $booksCollectionByDate->toArray();
        $booksSortedByDate = array_slice($allBooks, 0, 2);
        $allBooksMinusTwo = array_slice($allBooks, 2);

        $randomSortedArray = [];
        if (is_array($allBooksMinusTwo)) {
            $shuffleResult = shuffle($allBooksMinusTwo);
            if ($shuffleResult) {
                $randomSortedArray = $allBooksMinusTwo;
            }
        }

        //Random sorting
        $completedCollection = collect(
            array_merge(
                $booksSortedByDate,
                $randomSortedArray
            ))->unique()->take($limit);

        return $completedCollection;
    }

    /**
     * Generate link for book on Facebook
     *
     * @param $isbn13
     * @return string
     */
    protected static function getAmazonLink($isbn13)
    {
        return 'https://www.amazon.co.uk/s/ref=nb_sb_noss?url=search-alias%3Daps&x=0&y=0&field-keywords=' . $isbn13;
    }

    /**
     * Generate link for book on Facebook from pivot table
     *
     * @param $isbn13
     * @return string
     */
    protected static function getAmazonLinkFromPivot($isbn13)
    {
        $linkForAmazon = PanmcmillanBook::joinWithFormats(PanmcmillanBook ::query())
            ->where(PanmcmillanBook::TABLE_NAME . '.isbn13', '=', $isbn13)
            ->whereIn(PanmcmillanFormat::TABLE_NAME . '.format', self::PAPER_BOOKS)
            ->select(FormatPanmcmillanBook::TABLE_NAME . '.isbn13 as format_isbn13')
            ->first();

        if (empty($linkForAmazon)) {
            $linkForAmazon = SupaduBook::joinWithFormats(SupaduBook::query())
                ->where(SupaduBook::TABLE_NAME . '.isbn13', '=', $isbn13)
                ->whereIn(SupaduFormat::TABLE_NAME . '.format', self::PAPER_BOOKS)
                ->select(FormatSupaduBook::TABLE_NAME . '.isbn13 as format_isbn13')
                ->first();
        }

        if ($linkForAmazon != null) {
            return static::getAmazonLink($linkForAmazon['format_isbn13']);
        } else {
            return static::getAmazonLink($isbn13);
        }
    }

    /**
     * Generate link for book on Facebook
     *
     * @param $isbn13
     * @return string
     */
    public static function getPanmcmillanLink($isbn13)
    {
        return 'http://extracts.panmacmillan.com/extract?isbn=' . $isbn13;
    }

    /**
     * @return int
     */
    protected static function getDescriptionLength()
    {
        return static::$book_description_length;
    }

    /**
     * Clear string from html tags, convert all HTML entities to their applicable characters
     *
     * @param $dirtyString
     * @return string
     */
    protected static function clearString($dirtyString)
    {
        return trim(html_entity_decode(strip_tags($dirtyString)));
    }

    /**
     * @param bool $fiction
     * @param string|null $filterName - values == genre|author|keyword
     * @param string|null $filterValue
     * @param string|null $sortValue - values == publication_date|price|average_rating
     * @param string|null $sortSide - values = asc|desc
     * @param array|null $age - array ['from']|['to']
     * @param int $offset
     * @param int $limit
     * @param bool $anyFormat - format of the book, set false for paperback only
     * @return Collection
     */
    protected static function performComposedRequest(
        $fiction = null,
        $filterName = null,
        $filterValue = null,
        $sortValue = null,
        $sortSide = 'asc',
        $age = null,
        $offset = 0,
        $limit = 5,
        $anyFormat = true
    )
    {
        $query = FullJoinBook::query();
        //WHe need fiction in categories - use EXISTS when not - NOT EXISTS
        if ($fiction !== null) {
            $query = FullJoinBook::whereHasCategory($query, 'fiction', $fiction);
        }

        if ($filterName == 'carousel') {
            $query = FullJoinBook::whereHasCollection($query, $filterValue);
        }

        if ($filterName == 'genre') {
            $query = FullJoinBook::whereHasCategory($query, $filterValue, true);
        }

        //Filter books by format
        if ($anyFormat !== true) {

            $query = FullJoinBook::whereHasFormats($query, static::PAPER_BOOKS);
        }

        if ($filterName == 'author') {

            $query = FullJoinBook::whereHasAuthor($query, $filterValue);
        }

        if ($filterName == 'keyword') {
            $query->where(function ($query) use ($filterValue) {
                $query->where(PanmcmillanBook::TABLE_NAME . '_keynote', 'like', '%' . $filterValue . '%')
                    //OrWhere rests all previous queries, put it in closure
                    ->orWhere(PanmcmillanBook::TABLE_NAME . '_title', 'like', '%' . $filterValue . '%')
                    ->orWhere(SupaduBook::TABLE_NAME . '_title', 'like', '%' . $filterValue . '%');
            });
        }

        //Age filtering
        if (isset($age)) {
            $query->where(function ($query) use ($age) {

                if (isset($age['from'])) {
                    $query->orWhere(SupaduBook::TABLE_NAME . '_age_from', '=', (int)$age['from']);
                }

                if (isset($age['to'])) {
                    $ageFrom = (int)$age['from'];
                    $ageTo = (int)$age['to'];
                    //If age is 9-12 I should include 9,10,11,12
                    if ($ageFrom < $ageTo) {
                        $ageDiff = $ageTo - $ageFrom;
                        for ($i = 1; $i <= $ageDiff; $i++) {

                            $query->orWhere(SupaduBook::TABLE_NAME . '_age_from', '=', $ageFrom + $i);
                        }
                    }
                }
            });
        }

        if (!empty($sortValue)) {

            if (!isset($sortSide)) {
                $sortSide = 'asc';
            }

            if ($sortValue == CustomSortFilterStep::SORT_BY_CHEAPEST) {
                $sortValue = SupaduBook::TABLE_NAME . '_price';
                $query->orderBy($sortValue, $sortSide);
            }

            //Check sorting
            if ($sortValue == CustomSortFilterStep::SORT_BY_NEWEST) {
                $sortValue = PanmcmillanBook::TABLE_NAME . '_publication_date';
                $query->orderBy($sortValue, $sortSide);
                $sortValue = SupaduBook::TABLE_NAME . '_publication_date';
                $query->orderBy($sortValue, $sortSide);
            }
            if ($sortValue == CustomSortFilterStep::SORT_BY_POPULAR) {
                $sortValue = SupaduBook::TABLE_NAME . '_average_rating';
                $query->orderBy($sortValue, $sortSide);
                $sortValue = PanmcmillanBook::TABLE_NAME . '_average_rating';
                $query->orderBy($sortValue, $sortSide);
            }

        } else {
            //Default sorting is random
            $query->orderByRaw("RAND()");
        }

        $query->offset($offset);
        $query->limit($limit);
        
        $DBBooks = $query->get();


        return $DBBooks;
    }

    public static function getSupaduCustomCollection(
        $filterName = null,
        $filterValue = null,
        $sortValue = null,
        $sortSide = 'asc',
        $offset = 0,
        $keywords,
        $limit = 5,
        $bookFormat = null
    ) {
        if (is_array($keywords)) {
            $supaduKeyword = SupaduKeyword::whereIn('keyword', $keywords)->first();
        } else {
            $supaduKeyword = SupaduKeyword::where('keyword', $keywords)->first();
        }

        $collectionName = SupaduCollection::where('id', '=', $supaduKeyword->supadu_collection_id)->first();

        $query = FullJoinBook::query();
        $query = FullJoinBook::whereHasCollection($query, $collectionName->seo_name);

        $query->offset($offset);
        $query->limit($limit);

        $books = $query->get();

        $DBBooks = static::makeCarouselFromCollection($books);

        return $DBBooks;
    }
}