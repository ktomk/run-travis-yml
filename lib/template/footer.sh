printf '\n::endgroup::\n' # close potential group to show important message
echo -e "Done. Your build exited with $TRAVIS_TEST_RESULT."

travis_terminate $TRAVIS_TEST_RESULT
